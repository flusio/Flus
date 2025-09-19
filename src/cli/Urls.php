<?php

namespace App\cli;

use App\http;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Urls
{
    /**
     * Show the HTTP response returned by an URL.
     *
     * @request_param string url
     * @request_param string user-agent
     *
     * @response 400
     *     If the URL is invalid.
     * @response 500
     *     If the URL cannot be resolved.
     * @response 200
     */
    public function show(Request $request): Response
    {
        $url = $request->parameters->getString('url', '');
        $user_agent = $request->parameters->getString('user-agent');

        $url_is_valid = filter_var($url, FILTER_VALIDATE_URL) !== false;

        if (!$url_is_valid || empty($url)) {
            return Response::text(400, "`{$url}` is not a valid URL.");
        }

        $fetcher = new http\Fetcher(
            ignore_cache: true,
            ignore_rate_limit: true,
            user_agent: $user_agent,
        );

        try {
            $response = $fetcher->get($url);
        } catch (http\UnexpectedHttpError $error) {
            return Response::text(500, $error->getMessage());
        }

        return Response::text(200, (string)$response);
    }

    /**
     * Clear the cache of the given URL.
     *
     * @request_param string url
     *
     * @response 500
     *     If the cache cannot be cleared.
     * @response 200
     */
    public function uncache(Request $request): Response
    {
        $url = $request->parameters->getString('url', '');
        $url_is_valid = filter_var($url, FILTER_VALIDATE_URL) !== false;
        if (!$url_is_valid) {
            return Response::text(400, "`{$url}` is not a valid URL.");
        }

        $url_hash = \SpiderBits\Cache::hash($url);
        $cache_path = \App\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);

        $result = $cache->remove($url_hash);

        if ($result) {
            return Response::text(200, "Cache for {$url} ({$url_hash}) has been cleared.");
        } else {
            return Response::text(500, "Cache for {$url} ({$url_hash}) cannot be cleared.");
        }
    }
}
