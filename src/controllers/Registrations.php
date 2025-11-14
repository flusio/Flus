<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\mailers;
use App\models;
use App\services;
use Minz\Mailer;
use Minz\Request;
use Minz\Response;

/**
 * Handle the requests related to the registrations.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Registrations extends BaseController
{
    /**
     * Show the registration form.
     *
     * @response 302 /
     *     If the user is connected.
     * @response 302 /login
     *     If the registrations are closed.
     * @response 200
     *     On sucess.
     */
    public function new(): Response
    {
        if (auth\CurrentUser::get()) {
            return Response::redirect('home');
        }

        if (!\App\Configuration::areRegistrationsOpened()) {
            return Response::redirect('login');
        }

        $form = new forms\Registration();

        return Response::ok('registrations/new.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * Create a user.
     *
     * @request_param string email
     * @request_param string username
     * @request_param string password
     * @request_param bool accept_terms
     * @request_param bool accept_contact
     * @request_param string csrf_token
     *
     * @response 302 /
     *     If the user is connected.
     * @response 302 /login
     *     If the registrations are closed.
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /onboarding
     *     On sucess.
     */
    public function create(Request $request): Response
    {
        if (auth\CurrentUser::get()) {
            return Response::redirect('home');
        }

        if (!\App\Configuration::areRegistrationsOpened()) {
            return Response::redirect('login');
        }

        $user = new models\User();
        $form = new forms\Registration(model: $user);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('registrations/new.phtml', [
                'form' => $form,
            ]);
        }

        $user = $form->model();
        $user->save();

        services\UserService::initializeData($user);

        // Initialize the validation token
        $validation_token = new models\Token(1, 'day', 16);
        $validation_token->save();

        $user->validation_token = $validation_token->token;
        $user->save();

        // Initialize the current session
        $session = auth\CurrentUser::createBrowserSession($user);
        $session_token = $session->token();

        $mailer_job = new Mailer\Job();
        $mailer_job->performAsap(mailers\Users::class, 'sendAccountValidationEmail', $user->id);

        $response = Response::redirect('onboarding');
        $response->setCookie('session_token', $session_token->token, [
            'expires' => $session_token->expired_at->getTimestamp(),
            'samesite' => 'Lax',
        ]);
        return $response;
    }
}
