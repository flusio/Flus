<?php

namespace flusio\services;

use flusio\models;
use flusio\utils;

/**
 * This service helps to fetch links content. It's a wrapper around the
 * SpiderBits library.
 *
 * @phpstan-type Options array{
 *     'timeout': int,
 *     'rate_limit': bool,
 *     'cache': bool,
 *     'force_sync': bool,
 * }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkFetcher
{
    public const ERROR_RATE_LIMIT = -1;
    public const ERROR_UNKNOWN = 0;

    private \SpiderBits\Cache $cache;

    private \SpiderBits\Http $http;

    /** @var Options */
    private $options = [
        'timeout' => 10,
        'rate_limit' => true,
        'cache' => true,
        'force_sync' => false,
    ];

    /**
     * @param array{
     *     'timeout'?: int,
     *     'rate_limit'?: bool,
     *     'cache'?: bool,
     *     'force_sync'?: bool,
     * } $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        /** @var string */
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $this->cache = new \SpiderBits\Cache($cache_path);

        $this->http = new \SpiderBits\Http();
        /** @var string */
        $user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http->user_agent = $user_agent;
        $this->http->timeout = $this->options['timeout'];
    }

    /**
     * Fetch a link, set its properties and save it
     */
    public function fetch(models\Link $link): void
    {
        $info = $this->fetchUrl($link->url);

        if ($info['status'] === self::ERROR_RATE_LIMIT) {
            // In case of a rate limit error, skip the link so it can be
            // fetched later.
            return;
        }

        $link->fetched_at = \Minz\Time::now();
        $link->fetched_code = $info['status'];
        $link->fetched_error = null;
        $link->fetched_count = $link->fetched_count + 1;
        if (isset($info['error'])) {
            $link->fetched_error = $info['error'];
        }

        // we set the title only if it wasn't changed yet
        $title_never_changed = $link->title === $link->url;
        if (
            isset($info['title']) &&
            ($this->options['force_sync'] || $title_never_changed)
        ) {
            $link->title = $info['title'];
        }

        $reading_never_changed = $link->reading_time <= 0;
        if (
            isset($info['reading_time']) &&
            ($this->options['force_sync'] || $reading_never_changed)
        ) {
            $link->reading_time = $info['reading_time'];
        }

        $illustration_never_set = empty($link->image_filename);
        if (
            isset($info['url_illustration']) &&
            ($this->options['force_sync'] || $illustration_never_set)
        ) {
            $image_service = new Image();
            $image_filename = $image_service->generatePreviews($info['url_illustration']);
            $link->image_filename = $image_filename;
        }

        if (!empty($info['url_feeds'])) {
            $link->url_feeds = $info['url_feeds'];
        } elseif ($this->isYoutube($link->url)) {
            $link->url_feeds = $this->urlToYoutubeFeeds($link->url);
        }

        $link->save();
    }

    /**
     * Fetch URL content and return information about the page
     *
     * @param string $url
     *
     * @return array{
     *     'status': int,
     *     'error'?: string,
     *     'title'?: string,
     *     'reading_time'?: int,
     *     'url_illustration'?: string,
     *     'url_feeds'?: string[],
     * }
     */
    public function fetchUrl(string $url): array
    {
        // First, we get information about rate limit and IP to select to
        // execute the request (for Youtube).
        /** @var string[] */
        $server_ips = \Minz\Configuration::$application['server_ips'];
        if (!$this->options['rate_limit']) {
            // rate limit is disabled for this call
            $is_rate_limited = false;
            $selected_ip = null;
        } elseif ($server_ips && $this->isYoutube($url)) {
            // Youtube has a strict rate limit (1 req/min), so we load balance
            // the requests on different IPs if the admin set the options.
            $is_rate_limited = true;
            $selected_ip = null;
            // shuffle the IPs so it's not always the same IPs that fetch
            // Youtube
            shuffle($server_ips);
            foreach ($server_ips as $server_ip) {
                // we calculate the rate limit for the given IP and if it
                // hasn't been reached, we select the IP to be passed to Curl.
                $is_rate_limited = models\FetchLog::hasReachedRateLimit(
                    $url,
                    'link',
                    $server_ip
                );
                if (!$is_rate_limited) {
                    $selected_ip = $server_ip;
                    break;
                }
            }
        } else {
            // the default case where we calculate the reach limit and select
            // no interface.
            $is_rate_limited = models\FetchLog::hasReachedRateLimit($url, 'link');
            $selected_ip = null;
        }

        // Then, we "GET" the URL...
        $url_hash = \SpiderBits\Cache::hash($url);
        $cached_response = $this->cache->get($url_hash);
        if ($this->options['cache'] && $cached_response) {
            // ... via the cache
            $response = \SpiderBits\Response::fromText($cached_response);
        } elseif (!$is_rate_limited) {
            // ... or via HTTP
            $options = [
                'max_size' => 20 * 1024 * 1024,
            ];
            if ($this->isTwitter($url)) {
                // If we fetch Twitter, we need to alter our user agent to get
                // server-side rendered content.
                // @see https://stackoverflow.com/a/64332370
                $options['user_agent'] = $this->http->user_agent . ' (compatible; bot)';
            }

            if ($selected_ip) {
                $options['interface'] = $selected_ip;
                models\FetchLog::log($url, 'link', $selected_ip);
            } else {
                models\FetchLog::log($url, 'link');
            }

            try {
                $response = $this->http->get($url, [], $options);
            } catch (\SpiderBits\HttpError $e) {
                return [
                    'status' => self::ERROR_UNKNOWN,
                    'error' => $e->getMessage(),
                ];
            }

            // that we add to cache on success
            if ($response->success) {
                $this->cache->save($url_hash, (string)$response);
            }
        } else {
            return [
                'status' => self::ERROR_RATE_LIMIT,
                'error' => 'Reached rate limit',
            ];
        }

        $info = [
            'status' => $response->status,
            'url_feeds' => [],
        ];

        $data = $response->utf8Data();

        if (!$response->success) {
            // Okay, Houston, we've had a problem here. Return early, there's
            // nothing more to do.
            $info['error'] = $data;
            return $info;
        }

        /** @var string */
        $content_type = $response->header('content-type', '');
        if (
            \SpiderBits\feeds\Feed::isFeedContentType($content_type) &&
            \SpiderBits\feeds\Feed::isFeed($data)
        ) {
            // If we detect a feed, we add the URL to the list of feeds and
            // return directly since we operate on HTML only.
            $info['url_feeds'][] = $url;
            return $info;
        }

        if ($content_type && !str_contains($content_type, 'text/html')) {
            // We operate on HTML only. If content type is not declared, we
            // examine data hoping for HTML.
            return $info; // @codeCoverageIgnore
        }

        $dom = \SpiderBits\Dom::fromText($data);

        // Parse the title from the DOM
        $title = \SpiderBits\DomExtractor::title($dom);
        if ($title) {
            $info['title'] = $title;
        }

        if ($title && $this->isTwitter($url)) {
            // For Twitter, it's better to concatenate description. Title
            // should be "[account] on Twitter" while description is the tweet
            // content.
            $description = \SpiderBits\DomExtractor::description($dom);
            if ($description) {
                // we rebuild the title to translate it
                $twitter_account = utils\Belt::stripsEnd($title, ' on Twitter');
                $info['title'] = vsprintf(_('%s on Twitter: %s'), [$twitter_account, $description]);
            }
        }

        // And roughly estimate the reading time
        $content = \SpiderBits\DomExtractor::content($dom);
        $words = array_filter(explode(' ', $content));
        $info['reading_time'] = intval(count($words) / 200);

        // Get the illustration URL if any
        $url_illustration = \SpiderBits\DomExtractor::illustration($dom);
        $url_illustration = \SpiderBits\Url::sanitize($url_illustration);
        if ($url_illustration) {
            $info['url_illustration'] = $url_illustration;
        }

        $url_feeds = \SpiderBits\DomExtractor::feeds($dom);
        foreach ($url_feeds as $url_feed) {
            $url_feed = \SpiderBits\Url::absolutize($url_feed, $url);
            $url_feed = \SpiderBits\Url::sanitize($url_feed);
            $info['url_feeds'][] = $url_feed;
        }

        return $info;
    }

    /**
     * Return true if the url is pointing to Twitter
     *
     * @param string $url
     *
     * @return boolean
     */
    private function isTwitter($url)
    {
        $host = utils\Belt::host($url);
        return str_ends_with($host, 'twitter.com');
    }

    /**
     * Return true if the url is pointing to Youtube
     */
    private function isYoutube(string $url): bool
    {
        $host = utils\Belt::host($url);
        return str_ends_with($host, 'youtube.com');
    }

    /**
     * Return feed URL corresponding to a Youtube URL
     *
     * @see https://github.com/kevinpapst/freshrss-youtube/
     *
     * @return string[]
     */
    private function urlToYoutubeFeeds(string $url): array
    {
        $parsed_url = parse_url($url);
        if (!isset($parsed_url['host']) || $parsed_url['host'] !== 'www.youtube.com') {
            return [];
        }

        if (isset($parsed_url['path'])) {
            $path = $parsed_url['path'];
        } else {
            $path = '';
        }

        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $query);
        } else {
            $query = [];
        }

        $feeds = [];

        // I didn’t succeed to find a channel or user page which doesn’t
        // display an autodiscovered feed <link>, so these two regex MIGHT be
        // useless, but I prefer to keep them for now.
        if (preg_match('#^/channel/([0-9a-zA-Z_-]{6,36})#', $path, $matches) === 1) {
            $feeds[] = 'https://www.youtube.com/feeds/videos.xml?channel_id=' . $matches[1];
        }
        if (preg_match('#^/user/([0-9a-zA-Z_-]{6,36})#', $path, $matches) === 1) {
            $feeds[] = 'https://www.youtube.com/feeds/videos.xml?user=' . $matches[1];
        }

        // However, Youtube doesn’t display <link> for playlists.
        if (is_string($query['list'] ?? null)) {
            $feeds[] = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . $query['list'];
        }

        return $feeds;
    }
}
