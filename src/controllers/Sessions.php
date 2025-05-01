<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\utils;

/**
 * Handle the requests related to the current session.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sessions extends BaseController
{
    /**
     * Show the login form.
     *
     * @request_param string redirect_to A URL to redirect to (optional, default is `/`)
     *
     * @response 302 :redirect_to if already connected
     * @response 200
     */
    public function new(Request $request): Response
    {
        $redirect_to = $request->parameters->getString('redirect_to', \Minz\Url::for('home'));
        if (auth\CurrentUser::get()) {
            return Response::found($redirect_to);
        }

        $email = '';
        $password = '';

        if (\App\Configuration::$application['demo']) {
            $email = 'demo@flus.io';
            $password = 'demo';
        }

        return Response::ok('sessions/new.phtml', [
            'email' => $email,
            'password' => $password,
            'redirect_to' => $redirect_to,
        ]);
    }

    /**
     * Login / create a Session for the user
     *
     * @request_param string csrf
     * @request_param string email
     * @request_param string password
     * @request_param string redirect_to A URL to redirect to (optional, default is `/`)
     *
     * @response 302 :redirect_to if already connected
     * @response 400 if CSRF is invalid, email doesn't match with a User or if
     *               password is wrong
     * @response 302 :redirect_to if logged in
     */
    public function create(Request $request): Response
    {
        $redirect_to = $request->parameters->getString('redirect_to', \Minz\Url::for('home'));

        if (!$this->isPathRedirectable($redirect_to)) {
            $redirect_to = \Minz\Url::for('home');
        }

        if (auth\CurrentUser::get()) {
            return Response::found($redirect_to);
        }

        $email = $request->parameters->getString('email', '');
        $password = $request->parameters->getString('password', '');
        $csrf = $request->parameters->getString('csrf', '');

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('sessions/new.phtml', [
                'email' => $email,
                'password' => $password,
                'redirect_to' => $redirect_to,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $email = \Minz\Email::sanitize($email);
        if (!\Minz\Email::validate($email)) {
            return Response::badRequest('sessions/new.phtml', [
                'email' => $email,
                'password' => $password,
                'redirect_to' => $redirect_to,
                'errors' => [
                    'email' => _('The address email is invalid.'),
                ],
            ]);
        }

        $user = models\User::findBy([
            'email' => $email,
        ]);
        if (!$user) {
            return Response::badRequest('sessions/new.phtml', [
                'email' => $email,
                'password' => $password,
                'redirect_to' => $redirect_to,
                'errors' => [
                    'email' => _('We can’t find any account with this email address.'),
                ],
            ]);
        }

        if ($user->isSupportUser()) {
            return Response::badRequest('sessions/new.phtml', [
                'email' => $email,
                'password' => $password,
                'redirect_to' => $redirect_to,
                'error' => _('What are you trying to do? You can’t login to the support account.'),
            ]);
        }

        if (!$user->verifyPassword($password)) {
            return Response::badRequest('sessions/new.phtml', [
                'email' => $email,
                'password' => $password,
                'redirect_to' => $redirect_to,
                'errors' => [
                    'password_hash' => _('The password is incorrect.'),
                ],
            ]);
        }

        // The session cookie will probably expire before, but it's another
        // security barrier.
        $token = new models\Token(1, 'month');
        $token->save();

        $user_agent = $request->headers->getString('User-Agent', '');
        $ip = $request->ip();
        $session = new models\Session($user_agent, $ip);
        $session->user_id = $user->id;
        $session->token = $token->token;
        $session->save();

        auth\CurrentUser::setSessionToken($token->token);

        $response = Response::found($redirect_to);
        $response->setCookie('session_token', $token->token, [
            'expires' => $token->expired_at->getTimestamp(),
            'samesite' => 'Lax',
        ]);
        return $response;
    }

    /**
     * Change the current locale.
     *
     * @request_param string csrf
     * @request_param string locale
     * @request_param string redirect_to A URL to redirect to (optional, default is `/`)
     *
     * @response 302 :redirect_to
     */
    public function changeLocale(Request $request): Response
    {
        $locale = $request->parameters->getString('locale', '');
        $redirect_to = $request->parameters->getString('redirect_to', \Minz\Url::for('home'));
        $csrf = $request->parameters->getString('csrf', '');

        if (!\App\Csrf::validate($csrf)) {
            return Response::found($redirect_to);
        }

        $available_locales = utils\Locale::availableLocales();
        if (isset($available_locales[$locale])) {
            $_SESSION['locale'] = $locale;
        } else {
            \Minz\Log::warning(
                "[Sessions#changeLocale] Tried to set invalid `{$locale}` locale."
            );
        }

        return Response::found($redirect_to);
    }

    /**
     * Delete the current user session and logout the user.
     *
     * @request_param string csrf
     *
     * @response 302 /
     */
    public function delete(Request $request): Response
    {
        $current_user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('home'));

        $csrf = $request->parameters->getString('csrf', '');
        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::redirect('home');
        }

        $session = auth\CurrentUser::session();

        assert($session !== null);

        models\Session::delete($session->id);
        auth\CurrentUser::reset();

        $response = Response::redirect('home');
        $response->removeCookie('session_token');
        return $response;
    }
}
