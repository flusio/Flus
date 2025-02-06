<?php

namespace App\http;

use SpiderBits\Response;

// TODO clean the cache entries and files
// TODO write tests
class Cache
{
    public readonly string $cache_path;

    public function __construct(
        public readonly int $min_duration,
        public readonly int $max_duration,
    ) {
        $this->cache_path = \App\Configuration::$application['cache_path'];
    }

    public function get(string $url): ?Response
    {
        $key = self::hash($url);
        $cache_entry = CacheEntry::findByKey($key);

        if (!$cache_entry) {
            return null;
        }

        if ($cache_entry->hasExpired()) {
            $cache_entry->remove();
            return null;
        }

        $cache_response_path = $this->cache_path . '/' . $cache_entry->response_path;

        $response_compressed = @file_get_contents($cache_response_path);

        if (!$response_compressed) {
            $cache_entry->remove();
            return null;
        }

        $response_text = @gzdecode($response_compressed);

        if ($response_text === false) {
            $cache_entry->remove();
            return null;
        }

        return Response::fromText($response_text);
    }

    public function save(string $url, Response $response): void
    {
        $code = $response->status;

        if ($code < 200 || $code === 206 || $code === 304) {
            // Don't cache the response if the response is not finale, or if
            // status is 206 or 304 as we don't handle these special codes.
            return;
        }

        $cache_control = $response->header('Cache-Control', '');
        $age = $response->header('Age', '0');
        $expires = $response->header('Expires', '');
        $retry_after = $response->header('Retry-After', '0');

        $directives = $this->parseCacheControl($cache_control);

        if (
            isset($directives['no-store']) ||
            isset($directives['private']) ||
            isset($directives['must-understand'])
        ) {
            // These directives tell us to not cache the response.
            return;
        }

        $duration = 0;

        if (isset($directives['no-cache'])) {
            $duration = $this->min_duration;
        } elseif (isset($directives['max-age'])) {
            $max_age = (int) $directives['max-age'];
            $age = (int) $age;
            $duration = $max_age - $age;
        } elseif ($expires) {
            $expired_at = $this->parseHttpDate($expires);

            if ($expired_at === null) {
                $expired_at = \Minz\Time::now();
            }

            $expires_timestamp = $expired_at->getTimestamp();
            $now_timestamp = \Minz\Time::now()->getTimestamp();

            $duration = $expires_timestamp - $now_timestamp;
        } elseif ($code === 429) {
            $retry_at = $this->parseHttpDate($retry_after);

            if ($retry_at === null) {
                $duration = (int) $retry_after;
            } else {
                $retry_at_timestamp = $retry_at->getTimestamp();
                $now_timestamp = \Minz\Time::now()->getTimestamp();

                $duration = $retry_at_timestamp - $now_timestamp;
            }
        }

        $duration = max($this->min_duration, $duration);
        $duration = min($this->max_duration, $duration);
        $expired_at_timestamp = \Minz\Time::now()->getTimestamp() + $duration;
        $expired_at = new \DateTimeImmutable('@' . $expired_at_timestamp);

        $key = self::hash($url);
        $cache_entry = new CacheEntry($key, $url, $expired_at);

        $cache_response_path = $this->cache_path . '/' . $cache_entry->response_path;
        $response_text = (string) $response;

        $response_compressed = @gzencode($response_text);

        if (!$response_compressed) {
            throw new CacheError("Response to {$cache_entry->url} cannot be compressed");
        }

        $result = @file_put_contents($cache_response_path, $response_compressed);

        if ($result === false) {
            throw new CacheError("Response to {$cache_entry->url} cannot be saved in cache");
        }

        $cache_entry->save();
    }

    public static function hash(string $url): string
    {
        return hash('sha256', $url);
    }

    /**
     * @return array<string, string|true>
     */
    private function parseCacheControl(string $cache_control): array
    {
        $directives = [];

        $cache_control_parts = explode(',', $cache_control);

        foreach ($cache_control_parts as $part) {
            $part = trim($part);

            if (str_contains($part, '=')) {
                list($directive, $value) = explode('=', $part, 2);
            } else {
                $directive = $part;
                $value = true;
            }

            $directive = strtolower($directive);

            $directives[$directive] = $value;
        }

        return $directives;
    }

    private function parseHttpDate(string $expires): ?\DateTimeImmutable
    {
        $formats = [
            \DateTimeInterface::RFC7231,
            \DateTimeInterface::RFC850,
            // Ignore the ANSI C's asctime() format as obsolete and more
            // difficult to parse.
        ];

        foreach ($formats as $format) {
            $expired_at = \DateTimeImmutable::createFromFormat($format, $expires);

            if ($expired_at) {
                return $expired_at;
            }
        }

        return null;
    }
}
