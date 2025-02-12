<?php

namespace App\services;

use App\http;
use App\models;
use App\utils;

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

    private http\Fetcher $fetcher;

    private bool $force_sync;

    /**
     * @param array{
     *     force_sync?: bool,
     *     http_timeout?: int,
     *     ignore_cache?: bool,
     *     ignore_rate_limit?: bool,
     * } $options
     */
    public function __construct(array $options = [])
    {
        $this->fetcher = new http\Fetcher(
            http_timeout: $options['http_timeout'] ?? 10,
            ignore_cache: $options['ignore_cache'] ?? false,
            ignore_rate_limit: $options['ignore_rate_limit'] ?? false,
        );

        $this->force_sync = $options['force_sync'] ?? false;
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

        $link->to_be_fetched = self::shouldBeFetchedAgain($link);

        // we set the title only if it wasn't changed yet
        $title_never_changed = $link->title === $link->url;
        if (
            isset($info['title']) &&
            ($this->force_sync || $title_never_changed)
        ) {
            $link->title = $info['title'];
        }

        $reading_never_changed = $link->reading_time <= 0;
        if (
            isset($info['reading_time']) &&
            ($this->force_sync || $reading_never_changed)
        ) {
            $link->reading_time = $info['reading_time'];
        }

        $illustration_never_set = empty($link->image_filename);
        if (
            !empty($info['url_illustration']) &&
            ($this->force_sync || $illustration_never_set)
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
     * @throws \DomainException if $url is empty
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
        if (empty($url)) {
            throw new \DomainException('URL cannot be empty');
        }

        try {
            $response = $this->fetcher->get($url, type: 'link');
        } catch (http\RateLimitError $e) {
            return [
                'status' => self::ERROR_RATE_LIMIT,
                'error' => 'Reached rate limit',
            ];
        } catch (http\UnexpectedHttpError $e) {
            return [
                'status' => self::ERROR_UNKNOWN,
                'error' => $e->getMessage(),
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

        if (!$data) {
            return $info;
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

        $info['reading_time'] = \SpiderBits\DomExtractor::duration($dom);

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

    public static function shouldBeFetchedAgain(models\Link $link): bool
    {
        if (!$link->to_be_fetched) {
            return false;
        }

        $never_fetched = $link->fetched_at === null;

        if ($never_fetched) {
            return true;
        }

        $in_error = $link->fetched_code < 200 || $link->fetched_code >= 300;
        $reached_max_retries = $link->fetched_count > 25;
        $should_retry = $in_error && !$reached_max_retries;

        return $should_retry;
    }

    /**
     * Return true if the url is pointing to Twitter
     */
    private function isTwitter(string $url): bool
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
        return (
            str_ends_with($host, 'youtube.com') ||
            str_ends_with($host, 'youtu.be')
        );
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
