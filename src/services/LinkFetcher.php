<?php

namespace flusio\services;

use flusio\models;
use flusio\utils;

/**
 * This service helps to fetch links content. It's a wrapper around the
 * SpiderBits library.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkFetcher
{
    public const ERROR_RATE_LIMIT = -1;
    public const ERROR_UNKNOWN = 0;

    /** @var \SpiderBits\Cache */
    private $cache;

    /** @var \SpiderBits\Http */
    private $http;

    /** @var array */
    private $options = [
        'timeout' => 10,
        'rate_limit' => true,
        'cache' => true,
    ];

    /**
     * @param array $options
     *     A list of options where possible keys are:
     *     - timeout (integer)
     *     - rate_limit (boolean)
     *     - cache (boolean)
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);

        $cache_path = \Minz\Configuration::$application['cache_path'];
        $this->cache = new \SpiderBits\Cache($cache_path);

        $this->http = new \SpiderBits\Http();
        $this->http->user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http->timeout = $this->options['timeout'];
    }

    /**
     * Fetch a link, set its properties and save it
     *
     * @param \flusio\models\Link
     */
    public function fetch($link)
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
        if ($link->title === $link->url && isset($info['title'])) {
            $link->title = $info['title'];
        }

        if (isset($info['reading_time'])) {
            $link->reading_time = $info['reading_time'];
        }

        if (isset($info['url_illustration'])) {
            $image_service = new Image();
            $image_filename = $image_service->generatePreviews($info['url_illustration']);
            $link->image_filename = $image_filename;
        }

        if (!empty($info['url_feeds'])) {
            $link->url_feeds = json_encode($info['url_feeds']);
        } elseif ($this->isYoutube($link->url)) {
            $link->url_feeds = json_encode($this->urlToYoutubeFeeds($link->url));
        }

        $link->save();
    }

    /**
     * Fetch URL content and return information about the page
     *
     * @param string $url
     *
     * @return array Possible keys are:
     *     - status (always)
     *     - error
     *     - title
     *     - reading_time
     *     - url_illustration
     *     - url_feeds
     */
    public function fetchUrl($url)
    {
        // First, we "GET" the URL...
        $url_hash = \SpiderBits\Cache::hash($url);
        $cached_response = $this->cache->get($url_hash);
        if ($this->options['cache'] && $cached_response) {
            // ... via the cache
            $response = \SpiderBits\Response::fromText($cached_response);
        } elseif (
            !$this->options['rate_limit'] ||
            !models\FetchLog::hasReachedRateLimit($url)
        ) {
            // ... or via HTTP
            $options = [];
            if ($this->isTwitter($url)) {
                // If we fetch Twitter, we need to alter our user agent to get
                // server-side rendered content.
                // @see https://stackoverflow.com/a/64332370
                $options = [
                    'user_agent' => $this->http->user_agent . ' (compatible; Googlebot/2.1)',
                ];
            }

            models\FetchLog::log($url);
            try {
                $response = $this->http->get($url, [], $options);
            } catch (\SpiderBits\HttpError $e) {
                return [
                    'status' => self::ERROR_UNKNOWN,
                    'error' => $e->getMessage(),
                ];
            }

            // that we add to cache
            $this->cache->save($url_hash, (string)$response);
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

        // It's dangerous out there. mb_convert_encoding makes sure data is a
        // valid UTF-8 string.
        $encodings = mb_list_encodings();
        $data = mb_convert_encoding($response->data, 'UTF-8', $encodings);

        if (!$response->success) {
            // Okay, Houston, we've had a problem here. Return early, there's
            // nothing more to do.
            $info['error'] = $data;
            return $info;
        }

        $content_type = $response->header('content-type');
        if (
            \SpiderBits\feeds\Feed::isFeedContentType($content_type) &&
            \SpiderBits\feeds\Feed::isFeed($data)
        ) {
            // If we detect a feed, we add the URL to the list of feeds and
            // return directly since we operate on HTML only.
            $info['url_feeds'][] = $url;
            return $info;
        }

        if (!utils\Belt::contains($content_type, 'text/html')) {
            // We operate on HTML only
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
        return (
            utils\Belt::startsWith($url, 'https://twitter.com/') ||
            utils\Belt::startsWith($url, 'https://mobile.twitter.com/')
        );
    }

    /**
     * Return true if the url is pointing to Youtube
     *
     * @param string $url
     *
     * @return boolean
     */
    private function isYoutube($url)
    {
        $parsed_url = parse_url($url);
        return isset($parsed_url['host']) && $parsed_url['host'] === 'www.youtube.com';
    }

    /**
     * Return feed URL corresponding to a Youtube URL
     *
     * @see https://github.com/kevinpapst/freshrss-youtube/
     *
     * @param string $url
     *
     * @return string
     */
    private function urlToYoutubeFeeds($url)
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
        if (isset($query['list'])) {
            $feeds[] = 'https://www.youtube.com/feeds/videos.xml?playlist_id=' . $query['list'];
        }

        return $feeds;
    }
}
