<?php

namespace App\controllers\my;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Security
{
    use utils\InternalPathChecker;

    /**
     * @response 302 /login?redirect_to=/my/security
     *    If the user is not connected.
     * @response 302 /my/security/confirmation?from=/my/security
     *    If the password is not confirmed.
     * @response 200
     *    On success.
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

        if (!$session->isPasswordConfirmed()) {
            return Response::redirect('password confirmation', [
                'from' => \Minz\Url::for('security'),
            ]);
        }

        return Response::ok('my/security/show.phtml', [
            'email' => $user->email,
        ]);
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
     * @response 302 /my/security/confirmation?from=/my/security
     *    If the password is not confirmed.
     * @response 400
     *    If the CSRF token or the email is invalid.
     * @response 400
     *     If trying to change the demo account credentials if demo is enabled
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
            return Response::redirect('password confirmation', [
                'from' => \Minz\Url::for('security'),
            ]);
        }

        $email = $request->param('email', '');
        $password = $request->param('password', '');
        $csrf = $request->param('csrf', '');

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('my/security/show.phtml', [
                'email' => $email,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $demo = \App\Configuration::$application['demo'];
        if ($demo && $user->email === 'demo@flus.io') {
            return Response::badRequest('my/security/show.phtml', [
                'email' => $email,
                'error' => _('Sorry but you cannot change the login details for the demo account 😉'),
            ]);
        }

        $old_email = $user->email;
        $old_password_hash = $user->password_hash;

        $user->setLoginCredentials($email, $password);

        $existing_user = models\User::findBy(['email' => $user->email]);
        $email_exists = $existing_user && $existing_user->id !== $user->id;
        if ($email_exists) {
            return Response::badRequest('my/security/show.phtml', [
                'email' => $email,
                'errors' => [
                    'email' => _('An account already exists with this email address.'),
                ],
            ]);
        }

        $errors = $user->validate();
        if ($errors) {
            return Response::badRequest('my/security/show.phtml', [
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
     * Show a form to confirm the password of the user. It is required to
     * perform some sensitive actions.
     *
     * @request_param string from
     *
     * @response 302 /login?redirect_to=/my/security/confirmation
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function confirmation(Request $request): Response
    {
        $from = $request->param('from', \Minz\Url::for('security'));

        if (!$this->isInternalPath($from)) {
            $from = \Minz\Url::for('security');
        }

        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $session = auth\CurrentUser::session();

        assert($session !== null);

        if ($session->isPasswordConfirmed()) {
            return Response::found($from);
        }

        return Response::ok('my/security/confirmation.phtml', [
            'from' => $from,
        ]);
    }

    /**
     * Confirm the password for the current session.
     *
     * @request_param string csrf
     * @request_param string password
     * @request_param string from
     *
     * @response 302 /login?redirect_to=/my/security/confirmation
     *    If the user is not connected
     * @response 400
     *    If the CSRF token is invalid or if the password is invalid
     * @response 302 :from
     *    On success
     */
    public function confirm(Request $request): Response
    {
        $from = $request->param('from', \Minz\Url::for('security'));

        if (!$this->isInternalPath($from)) {
            $from = \Minz\Url::for('security');
        }

        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $password = $request->param('password', '');
        $csrf = $request->param('csrf', '');

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('my/security/confirmation.phtml', [
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if (!$user->verifyPassword($password)) {
            return Response::badRequest('my/security/confirmation.phtml', [
                'from' => $from,
                'errors' => [
                    'password_hash' => _('The password is incorrect.'),
                ],
            ]);
        }

        $session = auth\CurrentUser::session();

        assert($session !== null);

        $session->confirmed_password_at = \Minz\Time::now();
        $session->save();

        return Response::found($from);
    }
}
