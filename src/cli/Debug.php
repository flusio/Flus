<?php

namespace flusio\cli;

use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Debug
{
    /**
     * Show the HTTP response returned by an URL.
     *
     * @param_request string url
     *
     * @response 200
     */
    public function url($request)
    {
        $url = $request->param('url');

        $http = new \SpiderBits\Http();
        $response = $http->get($url);

        return Response::text(200, (string)$response);
    }
}
