<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\mailers;
use App\models;
use Minz\Mailer;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Passwords extends BaseController
{
    /**
     * Show the form to send email to reset the password.
     *
     * @response 302 /
     *     If the user is connected or if the demo is enabled.
     * @response 200
     *     On success.
     */
    public function forgot(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if ($user || \App\Configuration::isDemoEnabled()) {
            return Response::redirect('home');
        }

        $form = new forms\security\AskResetPassword();

        return Response::ok('passwords/forgot.phtml', [
            'form' => $form,
            'email_sent' => \Minz\Flash::pop('email_sent'),
        ]);
    }

    /**
     * Send a reset email.
     *
     * @request_param string email
     * @request_param string csrf_token
     *
     * @response 302 /
     *     If the user is connected or if the demo is enabled.
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /password/forgot
     *     On success.
     */
    public function reset(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if ($user || \App\Configuration::isDemoEnabled()) {
            return Response::redirect('home');
        }

        $form = new forms\security\AskResetPassword();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('passwords/forgot.phtml', [
                'form' => $form,
                'email_sent' => false,
            ]);
        }

        $user = $form->user();

        $reset_token = new models\Token(1, 'hour', 16);
        $reset_token->save();

        $user->reset_token = $reset_token->token;
        $user->save();

        $mailer_job = new Mailer\Job();
        $mailer_job->performAsap(mailers\Users::class, 'sendResetPasswordEmail', $user->id);

        \Minz\Flash::set('email_sent', true);

        return Response::redirect('forgot password');
    }

    /**
     * Show the edit form to change a password.
     *
     * @request_param string t
     *
     * @response 200
     *     On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the token doesn't exist.
     */
    public function edit(Request $request): Response
    {
        $token = models\Token::requireFromRequest($request, parameter: 't');
        $user = models\User::requireBy(['reset_token' => $token->token]);

        $form = new forms\security\ResetPassword([
            't' => $token->token,
        ]);

        return Response::ok('passwords/edit.phtml', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    /**
     * Update a password.
     *
     * @request_param string t
     * @request_param string password
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /
     *     On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the token doesn't exist.
     */
    public function update(Request $request): Response
    {
        $token = models\Token::requireFromRequest($request, parameter: 't');
        $user = models\User::requireBy(['reset_token' => $token->token]);

        $form = new forms\security\ResetPassword(model: $user);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('passwords/edit.phtml', [
                'user' => $user,
                'form' => $form,
            ]);
        }

        $user = $form->model();
        $user->save();

        // We make sure to clean token and sessions to prevent attacker to take
        // control back on the account
        if ($user->reset_token) {
            models\Token::delete($user->reset_token);
        }
        models\Session::deleteByUserId($user->id);

        // also, the user might be connected with a different account so we
        // make sure to log him in with the current one.
        $session = auth\CurrentUser::createBrowserSession($user);
        $session_token = $session->token();

        $response = Response::redirect('home');
        $response->setCookie('session_token', $session_token->token, [
            'expires' => $session_token->expired_at->getTimestamp(),
            'samesite' => 'Lax',
        ]);
        return $response;
    }
}
