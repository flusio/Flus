<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\jobs;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Passwords
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
        if ($user || \Minz\Configuration::$application['demo']) {
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
        if ($user || \Minz\Configuration::$application['demo']) {
            return Response::redirect('home');
        }

        $email = $request->param('email', '');
        $csrf = $request->param('csrf', '');

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

        if (!\Minz\Csrf::validate($csrf)) {
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

        $mailer_job = new jobs\Mailer();
        $mailer_job->performAsap('Users', 'sendResetPasswordEmail', $user->id);

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
        $t = $request->param('t', '');
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
        $t = $request->param('t', '');
        $password = $request->param('password', '');
        $csrf = $request->param('csrf', '');

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

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('passwords/edit.phtml', [
                'token' => $token->token,
                'email' => $user->email,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $user->password_hash = models\User::passwordHash($password);

        $errors = $user->validate();
        if ($errors) {
            return Response::badRequest('passwords/edit.phtml', [
                'token' => $token->token,
                'email' => $user->email,
                'errors' => $errors,
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
        auth\CurrentUser::reset();

        $session_token = new models\Token(1, 'month');
        $session_token->save();

        /** @var string */
        $user_agent = $request->header('HTTP_USER_AGENT', '');
        /** @var string */
        $ip = $request->header('REMOTE_ADDR', 'unknown');
        $session = new models\Session($user_agent, $ip);
        $session->user_id = $user->id;
        $session->token = $session_token->token;
        $session->save();

        auth\CurrentUser::setSessionToken($session_token->token);

        return Response::redirect('home');
    }
}
