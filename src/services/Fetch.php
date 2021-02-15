<?php

namespace flusio\services;

use flusio\utils;

/**
 * This service helps to fetch links content. It's a wrapper around the
 * SpiderBits library.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Fetch
{
    /** @var \SpiderBits\Cache */
    private $cache;

    /** @var \SpiderBits\Http */
    private $http;

    public function __construct()
    {
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $this->cache = new \SpiderBits\Cache($cache_path);

        $php_os = PHP_OS;
        $flusio_version = \Minz\Configuration::$application['version'];
        $this->http = new \SpiderBits\Http();
        $this->http->user_agent = "flusio/{$flusio_version} ({$php_os}; https://github.com/flusio/flusio)";
        $this->http->timeout = 5;
    }

    /**
     * Fetch a link and set its properties
     *
     * @param \flusio\models\Link
     */
    public function fetch($link)
    {
        $info = $this->fetchUrl($link->url);

        $link->fetched_at = \Minz\Time::now();
        $link->fetched_code = $info['status'];
        if (isset($info['error'])) {
            $link->fetched_error = $info['error'];
        }
        if (isset($info['title'])) {
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
     */
    public function fetchUrl($url)
    {
        // First, we "GET" the URL...
        $url_hash = \SpiderBits\Cache::hash($url);
        $cached_response = $this->cache->get($url_hash);
        if ($cached_response) {
            // ... via the cache
            $response = \SpiderBits\Response::fromText($cached_response);
        } else {
            // ... or via HTTP
            $response = $this->http->get($url);
            // that we add to cache
            $this->cache->save($url_hash, (string)$response);
        }

        $info = [
            'status' => $response->status,
        ];

        if (!$response->success) {
            // Okay, Houston, we've had a problem here. Return early, there's
            // nothing more to do.
            $info['error'] = $response->data;
            return $info;
        }

        $content_type = $response->header('content-type');
        if (!utils\Belt::contains($content_type, 'text/html')) {
            // We operate on HTML only
            return $info; // @codeCoverageIgnore
        }

        $dom = \SpiderBits\Dom::fromText($response->data);

        // Parse the title from the DOM
        $title = \SpiderBits\DomExtractor::title($dom);
        if ($title) {
            $info['title'] = $title;
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

        return $info;
    }
}
