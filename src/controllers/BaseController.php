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

    /**
     * Handle the \Minz\Errors\MissingRecordError to show a 404 page.
     */
    #[Controller\ErrorHandler(\Minz\Errors\MissingRecordError::class)]
    public function showNotFoundOnMissingRecordError(
        Request $request,
        \Minz\Errors\MissingRecordError $error,
    ): Response {
        return Response::notFound('not_found.phtml', [
            'error' => $error,
        ]);
    }
}
