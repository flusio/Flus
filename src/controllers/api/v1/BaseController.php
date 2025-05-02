<?php

namespace App\controllers\api\v1;

use Minz\Controller;
use Minz\Form;
use Minz\Request;
use Minz\Response;

/**
 * @phpstan-import-type ValidableError from \Minz\Validable
 *
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

    /**
     * Transform the initial request to a request where the JSON content (i.e. @input
     * param) keys => values are set as parameters.
     *
     * It only applies if the Content-Type header is "application/json".
     */
    protected function toJsonRequest(Request $request): Request
    {
        $jsonRequest = new Request($request->method(), $request->path());

        $content_type = $request->header('CONTENT_TYPE');

        if ($content_type === 'application/json') {
            $input = $request->paramJson('@input', []);

            foreach ($input as $key => $value) {
                $jsonRequest->setParam($key, $value);
            }
        }

        return $jsonRequest;
    }

    /**
     * Return a 400 error Response with structured errors.
     *
     * This is a helper to simplify returning errors in controllers from a
     * failing form.
     *
     * @template T of ?object
     *
     * @param Form<T> $form
     */
    protected function badRequestWithForm(Form $form): Response
    {
        $errors = $form->errors(format: false);
        $structured_errors = [];
        foreach ($errors as $field => $field_errors) {
            $structured_errors[$field] = [];

            foreach ($field_errors as $field_error) {
                $structured_errors[$field][] = [
                    'code' => $field_error[0],
                    'description' => $field_error[1],
                ];
            }
        }

        return Response::json(400, ['errors' => $structured_errors]);
    }
}
