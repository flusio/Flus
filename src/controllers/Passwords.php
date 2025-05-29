<?php

namespace App\controllers;

use Minz\Mailer;
use Minz\Request;
use Minz\Response;
use App\auth;
use App\mailers;
use App\models;

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
     *     If the user is connected or if the demo is enabled
     * @response 200
     */
    public function forgot(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if ($user || \App\Configuration::$application['demo']) {
            return Response::redirect('home');
        }

        return Response::ok('passwords/forgot.phtml', [
            'email' => '',
            'email_sent' => \Minz\Flash::pop('email_sent'),
        ]);
    }

    /**
     * Send a reset email.
     *
     * @request_param string csrf
     * @request_param string email
     *
     * @response 302 /
     *     If the user is connected or if the demo is enabled
     * @response 400
     *     If the csrf token or email is invalid
     * @response 302 /password/forgot
     *     On success
     */
    public function reset(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if ($user || \App\Configuration::$application['demo']) {
            return Response::redirect('home');
        }

        $email = $request->parameters->getString('email', '');
        $csrf = $request->parameters->getString('csrf', '');

        $email = \Minz\Email::sanitize($email);
        if (!\Minz\Email::validate($email)) {
            return Response::badRequest('passwords/forgot.phtml', [
                'email' => $email,
                'email_sent' => false,
                'errors' => [
                    'email' => _('The address email is invalid.'),
                ],
            ]);
        }

        $user = models\User::findBy([
            'email' => $email,
        ]);
        if (!$user) {
            return Response::badRequest('passwords/forgot.phtml', [
                'email' => $email,
                'email_sent' => false,
                'errors' => [
                    'email' => _('We can’t find any account with this email address.'),
                ],
            ]);
        }

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('passwords/forgot.phtml', [
                'email' => $email,
                'email_sent' => false,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

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
     * @response 404
     *     If the token doesn’t exist
     * @response 400
     *     If the token has expired or is invalid
     * @response 200
     */
    public function edit(Request $request): Response
    {
        $t = $request->parameters->getString('t', '');
        $token = models\Token::find($t);
        if (!$token) {
            return Response::notFound('passwords/edit.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $user = models\User::findBy(['reset_token' => $token->token]);
        if (!$user) {
            return Response::notFound('passwords/edit.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        if (!$token->isValid()) {
            return Response::badRequest('passwords/edit.phtml', [
                'error' => _('The token has expired, you should reset your password again.'),
            ]);
        }

        return Response::ok('passwords/edit.phtml', [
            'token' => $token->token,
            'email' => $user->email,
        ]);
    }

    /**
     * Update a password.
     *
     * @request_param string csrf
     * @request_param string t
     * @request_param string password
     *
     * @response 404
     *     If the token doesn’t exist
     * @response 400
     *     If the csrf token is invalid, if the token has expired or is
     *     invalid, or if the password is invalid
     * @response 302 /
     *     On success
     */
    public function update(Request $request): Response
    {
        $t = $request->parameters->getString('t', '');
        $password = $request->parameters->getString('password', '');
        $csrf = $request->parameters->getString('csrf', '');

        $token = models\Token::find($t);
        if (!$token) {
            return Response::notFound('passwords/edit.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $user = models\User::findBy(['reset_token' => $token->token]);
        if (!$user) {
            return Response::notFound('passwords/edit.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        if (!$token->isValid()) {
            return Response::badRequest('passwords/edit.phtml', [
                'error' => _('The token has expired, you should reset your password again.'),
            ]);
        }

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('passwords/edit.phtml', [
                'token' => $token->token,
                'email' => $user->email,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $user->password_hash = models\User::passwordHash($password);

        if (!$user->validate()) {
            return Response::badRequest('passwords/edit.phtml', [
                'token' => $token->token,
                'email' => $user->email,
                'errors' => $user->errors(),
            ]);
        }

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
