<?php

namespace App\controllers\api\v1;

use Minz\Request;
use Minz\Response;
use Minz\Controller;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class BaseController
{
    /**
     * Set CORS headers to all the responses returned by the API.
     */
    #[Controller\AfterAction]
    public function setCorsHeaders(Request $request, Response $response): void
    {
        $router = \Minz\Engine::router();

        assert($router !== null);

        $authorized_methods = $router->allowedMethodsForPath($request->path());
        $authorized_methods_without_options = array_diff($authorized_methods, ['OPTIONS']);

        if ($authorized_methods_without_options) {
            $response->setHeader('Access-Control-Allow-Origin', '*');
            $response->setHeader('Access-Control-Allow-Methods', implode(', ', $authorized_methods));
            $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
            $response->setHeader('Access-Control-Max-Age', '3600');
        }
    }

    /**
     * @response 404
     *     If the path cannot be served with a HTTP method different than OPTIONS
     * @response 200
     */
    public function options(Request $request): Response
    {
        $router = \Minz\Engine::router();

        assert($router !== null);

        $authorized_methods = $router->allowedMethodsForPath($request->path());
        $authorized_methods_without_options = array_diff($authorized_methods, ['OPTIONS']);

        if ($authorized_methods_without_options) {
            return new Response(200);
        } else {
            return new Response(404);
        }
    }
}
