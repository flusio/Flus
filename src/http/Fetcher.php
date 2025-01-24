<?php

namespace App\http;

use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Fetcher
{
    private \SpiderBits\Http $http;

    private \SpiderBits\Cache $cache;

    public function __construct(
        private int $http_timeout = 10,
        private int $http_max_size = 20 * 1024 * 1024,
        private int $cache_duration = 1 * 60 * 60 * 24,
        private bool $ignore_cache = false,
        private bool $ignore_rate_limit = false,
    ) {
        $this->http = new \SpiderBits\Http();
        $this->http->timeout = $this->http_timeout;

        $cache_path = \App\Configuration::$application['cache_path'];
        $this->cache = new \SpiderBits\Cache($cache_path);
    }

    public function get(string $url, string $type = 'default'): \SpiderBits\Response
    {
        $cached_response = $this->getCachedResponse($url);

        if ($cached_response) {
            return $cached_response;
        }

        $options = [
            'max_size' => $this->http_max_size,
            'user_agent' => $this->getUserAgent($url),
        ];

        $selected_ip = $this->getServerIp($url, $type);
        if ($selected_ip) {
            $options['interface'] = $selected_ip;
            FetchLog::log($url, $type, $selected_ip);
        } else {
            FetchLog::log($url, $type);
        }

        try {
            $response = $this->http->get($url, options: $options);
        } catch (\SpiderBits\HttpError $e) {
            throw new UnexpectedHttpError($url, $e);
        }

        // that we add to cache on success
        if ($response->success) {
            $this->cacheResponse($url, $response);
        }

        return $response;
    }

    private function getCachedResponse(string $url): ?\SpiderBits\Response
    {
        if ($this->ignore_cache) {
            return null;
        }

        $url_hash = \SpiderBits\Cache::hash($url);
        $cache_response = $this->cache->get($url_hash, $this->cache_duration);

        if (!$cache_response) {
            return null;
        }

        return \SpiderBits\Response::fromText($cache_response);
    }

    private function cacheResponse(string $url, \SpiderBits\Response $response): void
    {
        if ($this->ignore_cache) {
            return;
        }

        $url_hash = \SpiderBits\Cache::hash($url);
        $this->cache->save($url_hash, (string)$response);
    }

    private function getUserAgent(string $url): string
    {
        $user_agent = utils\UserAgent::get();

        // If we fetch Twitter or Youtube, we need to alter our user agent
        // to get server-side rendered content.
        if ($this->isTwitter($url)) {
            // @see https://stackoverflow.com/a/64332370
            return "{$user_agent} (compatible; bot)";
        } elseif ($this->isYoutube($url)) {
            // @see https://stackoverflow.com/a/46616889
            return "{$user_agent} (compatible; facebookexternalhit/1.1)";
        }

        return $user_agent;
    }

    private function getServerIp(string $type, string $url): ?string
    {
        $server_ips = \App\Configuration::$application['server_ips'];

        if (!$server_ips) {
            return null;
        }

        shuffle($server_ips);

        if ($this->ignore_rate_limit) {
            return $server_ips[0];
        }

        foreach ($server_ips as $server_ip) {
            // we calculate the rate limit for the given IP and if it hasn't
            // been reached, we select the IP to be passed to Curl.
            $reached_rate_limit = $this->hasReachedRateLimit(
                $url,
                $type,
                $server_ip
            );

            if (!$reached_rate_limit) {
                return $server_ip;
            }
        }

        throw new RateLimitError($url);
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
     * Determine if we reached the rate limit for the URL host.
     */
    private function hasReachedRateLimit(string $url, string $type, ?string $ip = null): bool
    {
        // Most of the time, we rate limit the requests to 25 requests per minute.
        $count_limit = 25;

        // We must be more drastic with Youtube servers though as they require
        // a limit of 1 req/min. The limit for the feeds seems to be higher,
        // but I didn't succeed to find the exact count.
        if ($this->isYoutube($url)) {
            if ($type === 'feed') {
                $count_limit = 10;
            } else {
                $count_limit = 1;
            }
        } else {
            $type = null;
        }

        $since = \Minz\Time::ago(1, 'minute');

        $count = FetchLog::countFetchesToHost($url, $since, $type, $ip);

        return $count >= $count_limit;
    }
}
