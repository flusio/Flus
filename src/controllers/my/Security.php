<?php

namespace flusio\controllers\my;

use Minz\Request;
use Minz\Response;
use flusio\auth;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Security
{
    /**
     * @response 302 /login?redirect_to=/my/security
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function show(): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('security'),
            ]);
        }

        $session = auth\CurrentUser::session();

        assert($session !== null);

        if ($session->isPasswordConfirmed()) {
            return Response::ok('my/security/show_confirmed.phtml', [
                'email' => $user->email,
            ]);
        } else {
            return Response::ok('my/security/show_to_confirm.phtml');
        }
    }

    /**
     * Update email and password of the user.
     *
     * @request_param string csrf
     * @request_param string email
     * @request_param string password
     *
     * @response 302 /login?redirect_to=/my/security
     *    If the user is not connected
     * @response 400
     *    If CSRF or email is invalid, or if the user didn't confirmed its
     *    password first
     * @response 302 /my/security
     *    On success
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('security'),
            ]);
        }

        $session = auth\CurrentUser::session();

        assert($session !== null);

        if (!$session->isPasswordConfirmed()) {
            return Response::badRequest('my/security/show_to_confirm.phtml', [
                'error' => _('You must confirm your password.'),
            ]);
        }

        $email = $request->param('email', '');
        $password = $request->param('password', '');
        $csrf = $request->param('csrf', '');

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('my/security/show_confirmed.phtml', [
                'email' => $email,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $old_email = $user->email;
        $old_password_hash = $user->password_hash;

        $user->setLoginCredentials($email, $password);

        $existing_user = models\User::findBy(['email' => $user->email]);
        $email_exists = $existing_user && $existing_user->id !== $user->id;
        if ($email_exists) {
            return Response::badRequest('my/security/show_confirmed.phtml', [
                'email' => $email,
                'errors' => [
                    'email' => _('An account already exists with this email address.'),
                ],
            ]);
        }

        $errors = $user->validate();
        if ($errors) {
            return Response::badRequest('my/security/show_confirmed.phtml', [
                'email' => $email,
                'errors' => $errors,
            ]);
        }

        $user->save();

        if ($user->email !== $old_email || $user->password_hash !== $old_password_hash) {
            // We make sure to clean token and sessions to prevent attacker to take
            // control back on the account
            if ($user->reset_token) {
                models\Token::delete($user->reset_token);
            }
            models\Session::deleteByUserId($user->id, $session->id);
        }

        return Response::redirect('security');
    }

    /**
     * Confirm the password is correct for the current session
     *
     * @request_param string csrf
     * @request_param string password
     *
     * @response 302 /login?redirect_to=/my/security
     *    If the user is not connected
     * @response 302 /my/security
     * @flash error
     *    If CSRF is invalid
     * @response 302 /my/security
     * @flash errors
     *    If password is invalid
     * @response 302 /my/security
     *    On success
     */
    public function confirmPassword(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('security'),
            ]);
        }

        $password = $request->param('password', '');
        $csrf = $request->param('csrf', '');

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::redirect('security');
        }

        if (!$user->verifyPassword($password)) {
            \Minz\Flash::set('errors', [
                'password_hash' => _('The password is incorrect.'),
            ]);
            return Response::redirect('security');
        }

        $session = auth\CurrentUser::session();

        assert($session !== null);

        $session->confirmed_password_at = \Minz\Time::now();
        $session->save();

        return Response::redirect('security');
    }
}
