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
     * Add the response code to the global context of the template.
     */
    #[Controller\AfterAction]
    public function addResponseCodeToTemplateContext(Request $request, Response $response): void
    {
        \Minz\Template\Twig::addGlobals([
            'response_code' => $response->code(),
        ]);
    }

    /**
     * Handle the MissingCurrentUserError to redirect to the login page.
     */
    #[Controller\ErrorHandler(auth\MissingCurrentUserError::class)]
    public function redirectOnMissingCurrentUser(
        Request $request,
        auth\MissingCurrentUserError $error,
    ): Response {
        return Response::redirect('login', [
            'redirect_to' => utils\RequestHelper::from($request),
        ]);
    }

    /**
     * Handle the PasswordNotConfirmedError to redirect to the password confirmation page.
     */
    #[Controller\ErrorHandler(auth\PasswordNotConfirmedError::class)]
    public function redirectOnPasswordNotConfirmedError(
        Request $request,
        auth\PasswordNotConfirmedError $error,
    ): Response {
        return Response::redirect('password confirmation', [
            'redirect_to' => utils\RequestHelper::from($request),
        ]);
    }

    /**
     * Handle the AccessDeniedError to show a 403 page.
     */
    #[Controller\ErrorHandler(auth\AccessDeniedError::class)]
    public function showForbiddenOnAccessDeniedError(
        Request $request,
        auth\AccessDeniedError $error,
    ): Response {
        return Response::forbidden('forbidden.html.twig', [
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
        return Response::notFound('errors/not_found.html.twig', [
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
        return Response::notFound('errors/not_found.html.twig', [
            'error' => $error,
        ]);
    }
}
