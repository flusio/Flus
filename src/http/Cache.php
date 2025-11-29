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
     * @see https://httpwg.org/specs/rfc9111.html
     */
    public function saveResponse(string $url, Response $response): void
    {
        $code = $response->status;

        if ($code < 200 || $code === 206 || $code === 304) {
            // Don't cache the response if the response is not finale, or if
            // status is 206 or 304 as we don't handle these special codes.
            return;
        }

        $cache_control_directives = $response->getCacheControlDirectives();

        if (isset($cache_control_directives['no-store']) || isset($cache_control_directives['no-cache'])) {
            // These directives tell us to not cache the response.
            // We may consider to use the min_duration though, but let's try
            // for a bit with this configuration.
            return;
        }

        $response_text = (string) $response;
        $response_compressed = @gzencode($response_text);

        if (!$response_compressed) {
            throw new CacheError("Response to {$url} cannot be compressed.");
        }

        $expires_at = $response->getRetryAfter(
            $this->default_duration,
            $this->min_duration,
            $this->max_duration,
        );

        $cache_item = $this->getItem($url);
        $cache_item->set($response_compressed);
        $cache_item->expiresAt($expires_at);
        $result = $this->save($cache_item);

        if ($result === false) {
            throw new CacheError("Response to {$url} cannot be saved in cache.");
        }
    }
}
