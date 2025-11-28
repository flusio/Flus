<?php

namespace App\http;

use App\cache as AppCache;
use SpiderBits\Response;

/**
 * A class who is able to cache HTTP responses based on their headers.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Cache extends AppCache\FileCache
{
    public function __construct(
        public readonly int $default_duration = 1 * 60 * 60,
        public readonly int $min_duration = 1 * 60 * 15,
        public readonly int $max_duration = 1 * 60 * 60 * 24 * 7,
    ) {
        parent::__construct(namespace: 'http');
    }

    /**
     * Returns a HTTP response from the cache if any.
     */
    public function getResponse(string $url): ?Response
    {
        $cache_item = $this->getItem($url);

        if (!$cache_item->isHit()) {
            return null;
        }

        if ($cache_item->hasExpired()) {
            $this->deleteItem($cache_item->getKey());
            return null;
        }

        $response_compressed = $cache_item->get();

        if (!is_string($response_compressed)) {
            $this->deleteItem($cache_item->getKey());
            return null;
        }

        $response_text = @gzdecode($response_compressed);

        if ($response_text === false) {
            $this->deleteItem($cache_item->getKey());
            return null;
        }

        return Response::fromText($response_text);
    }

    /**
     * Saves an HTTP response in the cache.
     *
     * The expiration duration is calculated based on HTTP headers of the
     * response.
     *
     * @see https://httpwg.org/specs/rfc9111.html
     */
    public function saveResponse(string $url, Response $response): void
    {
        $code = $response->status;

        if ($code < 200 || $code === 206 || $code === 304 || $code >= 500) {
            // Don't cache the response if the response is not finale, or if
            // status is 206 or 304 as we don't handle these special codes, or
            // if the server is in error.
            return;
        }

        $cache_control = $response->header('Cache-Control', '');
        $age = $response->header('Age', '0');
        $expires = $response->header('Expires', '');
        $retry_after = $response->header('Retry-After', '0');

        $directives = $this->parseCacheControl($cache_control);

        if (isset($directives['no-store']) || isset($directives['no-cache'])) {
            // These directives tell us to not cache the response.
            // We may consider to use the min_duration though, but let's try
            // for a bit with this configuration.
            return;
        }

        $duration = $this->default_duration;

        if (isset($directives['max-age'])) {
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
        } elseif ($response->status === 429) {
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
        $expiration = \Minz\Time::fromNow($duration, 'seconds');

        $response_text = (string) $response;
        $response_compressed = @gzencode($response_text);

        if (!$response_compressed) {
            throw new CacheError("Response to {$url} cannot be compressed.");
        }

        $cache_item = $this->getItem($url);
        $cache_item->set($response_compressed);
        $cache_item->expiresAt($expiration);
        $result = $this->save($cache_item);

        if ($result === false) {
            throw new CacheError("Response to {$url} cannot be saved in cache.");
        }
    }

    /**
     * Parses a "Cache-Control" HTTP header and returns an array with the
     * different cache directives.
     *
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

    /**
     * Parses an HTTP date header.
     */
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
