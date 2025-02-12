<?php

namespace App\http;

use App\utils;

/**
 * The Fetcher is the service responsible to make HTTP GET requests to external
 * websites.
 *
 * It handles GETting resources, caching them and load balancing the requests
 * over different IPs if configured.
 *
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
        /** @var array<string, string> */
        private array $headers = [],
    ) {
        $this->http = new \SpiderBits\Http();
        $this->http->timeout = $this->http_timeout;

        $cache_path = \App\Configuration::$application['cache_path'];
        $this->cache = new \SpiderBits\Cache($cache_path);
    }

    /**
     * Return a HTTP response for the GET $url request.
     *
     * If the response is in the cache, the fetcher doesn't perform a HTTP request.
     *
     * @param non-empty-string $url
     *
     * @throws RateLimitError if too many HTTP requests have been made to the host.
     * @throws UnexpectedHttpError if the HTTP request fails.
     */
    public function get(string $url, string $type = 'default'): \SpiderBits\Response
    {
        $cached_response = $this->getCachedResponse($url);

        if ($cached_response) {
            return $cached_response;
        }

        $selected_ip = $this->getServerIp($url, $type);

        if ($this->hasReachedRateLimit($url, $type, $selected_ip)) {
            throw new RateLimitError($url);
        }

        FetchLog::log($url, $type, $selected_ip);

        $options = [
            'max_size' => $this->http_max_size,
            'user_agent' => $this->getUserAgent($url),
            'interface' => $selected_ip,
            'headers' => $this->headers,
        ];

        try {
            $response = $this->http->get($url, options: $options);
        } catch (\SpiderBits\HttpError $e) {
            throw new UnexpectedHttpError($url, $e);
        }

        if ($response->success) {
            $this->cacheResponse($url, $response);
        }

        return $response;
    }

    /**
     * Return the cached response (if any) corresponding to the given URL.
     */
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

    /**
     * Cache a response corresponding to the given URL.
     */
    private function cacheResponse(string $url, \SpiderBits\Response $response): void
    {
        if ($this->ignore_cache) {
            return;
        }

        $url_hash = \SpiderBits\Cache::hash($url);
        $this->cache->save($url_hash, (string)$response);
    }

    /**
     * Return the user-agent for the given URL.
     *
     * The user-agent is defined at the application level, but can be altered
     * for some specific resources.
     *
     * @return non-empty-string
     */
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

    /**
     * Return an IP to fetch the given URL in order to load balance the
     * requests over different addresses and avoid to be blocked too easily.
     *
     * The IPs must be valid and defined with the APP_SERVER_IPS environment
     * variable.
     *
     * For now, this is only used for Youtube requests.
     *
     * @return ?non-empty-string
     */
    private function getServerIp(string $type, string $url): ?string
    {
        $server_ips = \App\Configuration::$application['server_ips'];

        if (!$server_ips) {
            return null;
        }

        if (!$this->isYoutube($url)) {
            return null;
        }

        shuffle($server_ips);

        foreach ($server_ips as $server_ip) {
            // We prefer to return an IP that isn't rate limited so the
            // selected IP is pertinent.
            $reached_rate_limit = $this->hasReachedRateLimit(
                $url,
                $type,
                $server_ip
            );

            if (!$reached_rate_limit) {
                return $server_ip;
            }
        }

        return null;
    }

    /**
     * Return true if the url is pointing to Twitter.
     */
    private function isTwitter(string $url): bool
    {
        $host = utils\Belt::host($url);
        return (
            $host === 'twitter.com' ||
            $host === 'x.com'
        );
    }

    /**
     * Return true if the url is pointing to Youtube.
     */
    private function isYoutube(string $url): bool
    {
        $host = utils\Belt::host($url);
        return (
            $host === 'youtube.com' ||
            $host === 'www.youtube.com' ||
            $host === 'youtu.be'
        );
    }

    /**
     * Determine if we reached the rate limit for the URL host.
     */
    private function hasReachedRateLimit(string $url, string $type, ?string $ip = null): bool
    {
        if ($this->ignore_rate_limit) {
            return false;
        }

        // Most of the time, we rate limit the requests to 25 requests per minute.
        $max_requests_per_minute = 25;

        // We must be more drastic with Youtube servers though as they require
        // a limit of 1 req/min. The limit for the feeds seems to be higher,
        // but I didn't succeed to find the exact count.
        if ($this->isYoutube($url)) {
            if ($type === 'feed') {
                $max_requests_per_minute = 10;
            } else {
                $max_requests_per_minute = 1;
            }
        } else {
            $type = null;
        }

        $since = \Minz\Time::ago(1, 'minute');

        $requests_per_minute = FetchLog::countFetchesToHost($url, $since, $type, $ip);

        return $requests_per_minute >= $max_requests_per_minute;
    }
}
