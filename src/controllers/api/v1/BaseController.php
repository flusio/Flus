<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use App\controllers\errors;
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
    #[Controller\BeforeAction]
    public function authenticateUser(Request $request): void
    {
        $authorization_header = $request->headers->getString('Authorization', '');

        $result = preg_match('/^Bearer (?P<token>\w+)$/', $authorization_header, $matches);
        if ($result === false || !isset($matches['token'])) {
            return;
        }

        $token = $matches['token'];
        auth\CurrentUser::authenticate($token, scope: 'api');
    }

    public function requireCurrentUser(): models\User
    {
        $current_user = auth\CurrentUser::get();

        if (!$current_user) {
            throw new errors\MissingCurrentUserError('');
        }

        return $current_user;
    }

    #[Controller\ErrorHandler(errors\MissingCurrentUserError::class)]
    public function failOnMissingCurrentUser(Request $request): Response
    {
        $response = Response::json(401, ['error' => 'The request is not authenticated.']);
        $this->setCorsHeaders($request, $response);
        return $response;
    }

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
        $content_type = $request->headers->getString('Content-Type', '');

        if ($content_type === 'application/json') {
            $input = $request->parameters->getJson('@input', []);
            return new Request($request->method(), $request->path(), parameters: $input);
        } else {
            return new Request($request->method(), $request->path(), parameters: []);
        }
    }

    /**
     * Return a 400 error Response with structured errors.
     *
     * This is a helper to simplify returning errors in controllers from a
     * failing form.
     *
     * @param array<string, ValidableError[]> $errors
     */
    protected function badRequest(array $errors): Response
    {
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
