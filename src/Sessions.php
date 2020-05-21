<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the current session.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sessions
{
    /**
     * Show the login form.
     *
     * @request_param string redirect_to An action pointer to redirect to (optional, default is `home`)
     *
     * @response 200
     * @response 302 $redirect_to if already connected
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function new($request)
    {
        $redirect_to = $request->param('redirect_to', 'home');
        if (utils\CurrentUser::get()) {
            return Response::redirect($redirect_to);
        }

        return Response::ok('sessions/new.phtml', [
            'email' => '',
            'password' => '',
            'redirect_to' => $redirect_to,
        ]);
    }

    /**
     * Login / create a Session for the user
     *
     * @request_param string csrf
     * @request_param string email
     * @request_param string password
     * @request_param string redirect_to An action pointer to redirect to (optional, default is `home`)
     *
     * @response 302 $redirect_to if logged in
     * @response 302 $redirect_to if already connected
     * @response 400 if CSRF is invalid, email doesn't match with a User or if
     *               password is wrong
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function create($request)
    {
        $redirect_to = $request->param('redirect_to', 'home');
        if (utils\CurrentUser::get()) {
            return Response::redirect($redirect_to);
        }

        $email = $request->param('email');
        $password = $request->param('password');
        $csrf = new \Minz\CSRF();

        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('sessions/new.phtml', [
                'email' => $email,
                'password' => $password,
                'redirect_to' => $redirect_to,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();
        $session_dao = new models\dao\Session();

        $user_raw = $user_dao->findBy([
            'email' => utils\Email::sanitize($email),
        ]);
        if (!$user_raw) {
            return Response::badRequest('sessions/new.phtml', [
                'email' => $email,
                'password' => $password,
                'redirect_to' => $redirect_to,
                'errors' => [
                    'email' => _('We can’t find any account with this email address.'),
                ],
            ]);
        }

        $user = new models\User($user_raw);
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
        $token = models\Token::init(1, 'month');
        $token_dao->save($token);

        $session_name = utils\Browser::format($request->header('HTTP_USER_AGENT', ''));
        $ip = $request->header('REMOTE_ADDR', 'unknown');
        $session = models\Session::init($session_name, $ip);
        $session->user_id = $user->id;
        $session->token = $token->token;
        $session_dao->save($session);

        utils\CurrentUser::setSessionToken($token->token);

        return Response::redirect($redirect_to);
    }

    /**
     * Change the current locale.
     *
     * @request_param string csrf
     * @request_param string locale
     * @request_param string redirect_to An action pointer to redirect to (optional, default is `home`)
     *
     * @response 302 $redirect_to
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function changeLocale($request)
    {
        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::redirect($request->param('redirect_to', 'home'));
        }

        $locale = $request->param('locale');
        $available_locales = utils\Locale::availableLocales();
        if (isset($available_locales[$locale])) {
            $_SESSION['locale'] = $locale;

            $current_user = utils\CurrentUser::get();
            if ($current_user) {
                $user_dao = new models\dao\User();
                $current_user->locale = $locale;
                $user_dao->save($current_user);
            }
        } else {
            \Minz\Log::warning(
                "[Sessions#changeLocale] Tried to set invalid `{$locale}` locale."
            );
        }

        return Response::redirect($request->param('redirect_to', 'home'));
    }
}
