<?php

namespace App\controllers;

use App\auth;
use App\models;
use Minz\Controller;
use Minz\Request;
use Minz\Response;

/**
 * The base controller used by all the other controllers to provide some
 * utility methods.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class BaseController
{
    /**
     * Return the logged-in user, or fail if there is none.
     *
     * @see \App\auth\CurrentUser::get
     *
     * @throws errors\MissingCurrentUserError
     *     If the user is not logged in.
     */
    public function requireCurrentUser(string $redirect_after_login): models\User
    {
        $current_user = auth\CurrentUser::get();

        if (!$current_user) {
            throw new errors\MissingCurrentUserError($redirect_after_login);
        }

        return $current_user;
    }

    /**
     * Handle the MissingCurrentUserError to redirect to the login page.
     */
    #[Controller\ErrorHandler(errors\MissingCurrentUserError::class)]
    public function redirectOnMissingCurrentUser(
        Request $request,
        errors\MissingCurrentUserError $error,
    ): Response {
        $redirect_to = $error->redirect_after_login;

        if (!$redirect_to) {
            $redirect_to = $request->selfUri();
        }

        return Response::redirect('login', [
            'redirect_to' => $redirect_to,
        ]);
    }

    public function isPathRedirectable(string $path): bool
    {
        $router = \Minz\Engine::router();

        if ($router === null) {
            return false;
        }

        try {
            $router->match('GET', $path);
            return true;
        } catch (\Minz\Errors\RouteNotFoundError $e) {
            return false;
        }
    }
}
