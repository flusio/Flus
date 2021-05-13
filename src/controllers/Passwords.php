<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Passwords
{
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
    public function edit($request)
    {
        $t = $request->param('t');
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
    public function update($request)
    {
        $t = $request->param('t');
        $password = $request->param('password');

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

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
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

        $connected_user = auth\CurrentUser::get();
        if (!$connected_user) {
            // Logs the user in
            $session_token = models\Token::init(1, 'month');
            $session_token->save();

            $session_name = utils\Browser::format($request->header('HTTP_USER_AGENT', ''));
            $ip = $request->header('REMOTE_ADDR', 'unknown');
            $session = models\Session::init($session_name, $ip);
            $session->user_id = $user->id;
            $session->token = $session_token->token;
            $session->save();

            auth\CurrentUser::setSessionToken($session_token->token);
        }

        return Response::redirect('home');
    }
}
