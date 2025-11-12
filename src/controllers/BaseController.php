<?php

namespace App\controllers;

use App\auth;
use App\models;
use App\utils;
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
     *
     * errors\MissingCurrentUserError is deprecated but is still handled by
     * this method.
     */
    #[Controller\ErrorHandler(errors\MissingCurrentUserError::class)]
    #[Controller\ErrorHandler(auth\MissingCurrentUserError::class)]
    public function redirectOnMissingCurrentUser(
        Request $request,
        errors\MissingCurrentUserError|auth\MissingCurrentUserError $error,
    ): Response {
        $redirect_to = '';
        if ($error instanceof errors\MissingCurrentUserError) {
            $redirect_to = $error->redirect_after_login;
        }

        if ($redirect_to === '') {
            $redirect_to = utils\RequestHelper::from($request);
        }

        $login_parameters = [];
        if ($redirect_to) {
            $login_parameters['redirect_to'] = $redirect_to;
        }

        return Response::redirect('login', $login_parameters);
    }

    /**
     * Handle the AccessDeniedError to show a 403 page.
     */
    #[Controller\ErrorHandler(auth\AccessDeniedError::class)]
    public function showForbiddenOnAccessDeniedError(
        Request $request,
        auth\AccessDeniedError $error,
    ): Response {
        return Response::forbidden('forbidden.phtml', [
            'error' => $error,
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

    /**
     * Handle the PaginationOutOfBoundsError to show a 404 page.
     */
    #[Controller\ErrorHandler(utils\PaginationOutOfBoundsError::class)]
    public function showNotFoundOnPaginationOutOfBoundsError(
        Request $request,
        utils\PaginationOutOfBoundsError $error,
    ): Response {
        return Response::notFound('not_found.phtml', [
            'error' => $error,
        ]);
    }
}
