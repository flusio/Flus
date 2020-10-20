<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the registrations.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Registrations
{
    /**
     * Show the registration form.
     *
     * @response 302 / if connected
     * @response 302 /login if registrations are closed
     * @response 200
     *
     * @return \Minz\Response
     */
    public function new()
    {
        if (utils\CurrentUser::get()) {
            return Response::redirect('home');
        }

        if (!\Minz\Configuration::$application['registrations_opened']) {
            return Response::redirect('login');
        }

        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        $has_terms = file_exists($terms_path);

        return Response::ok('registrations/new.phtml', [
            'has_terms' => $has_terms,
            'username' => '',
            'email' => '',
            'password' => '',
        ]);
    }

    /**
     * Create a user.
     *
     * @request_param string csrf
     * @request_param string email
     * @request_param string username
     * @request_param string password
     *
     * @response 302 / if already connected
     * @response 302 /login if registrations are closed
     * @response 400 if CSRF token is wrong
     * @response 400 if email, username or password is missing/invalid
     * @response 400 if email already exists
     * @response 302 /onboarding
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function create($request)
    {
        if (utils\CurrentUser::get()) {
            return Response::redirect('home');
        }

        if (!\Minz\Configuration::$application['registrations_opened']) {
            return Response::redirect('login');
        }

        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        $has_terms = file_exists($terms_path);

        $username = $request->param('username');
        $email = $request->param('email');
        $password = $request->param('password');
        $accept_terms = $request->param('accept_terms', false);
        $user_dao = new models\dao\User();
        $collection_dao = new models\dao\Collection();
        $token_dao = new models\dao\Token();
        $session_dao = new models\dao\Session();
        $csrf = new \Minz\CSRF();

        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('registrations/new.phtml', [
                'has_terms' => $has_terms,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $user = models\User::init($username, $email, $password);
        $user->locale = utils\Locale::currentLocale();

        $errors = $user->validate();
        if ($errors) {
            return Response::badRequest('registrations/new.phtml', [
                'has_terms' => $has_terms,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'errors' => $errors,
            ]);
        }

        if ($user_dao->findBy(['email' => $user->email])) {
            return Response::badRequest('registrations/new.phtml', [
                'has_terms' => $has_terms,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'errors' => [
                    'email' => _('An account already exists with this email address.'),
                ],
            ]);
        }

        if ($has_terms && !$accept_terms) {
            return Response::badRequest('registrations/new.phtml', [
                'has_terms' => $has_terms,
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'errors' => [
                    'accept_terms' => _('You must accept the terms of service.'),
                ],
            ]);
        }

        $validation_token = models\Token::init(1, 'day', 16);
        $token_dao->save($validation_token);

        $user->validation_token = $validation_token->token;
        $user_id = $user_dao->save($user);

        // Initialize the bookmarks collection
        $bookmarks_collection = models\Collection::initBookmarks($user_id);
        $collection_dao->save($bookmarks_collection);

        // Initialize the current session
        $session_token = models\Token::init(1, 'month');
        $token_dao->save($session_token);

        $session_name = utils\Browser::format($request->header('HTTP_USER_AGENT', ''));
        $ip = $request->header('REMOTE_ADDR', 'unknown');
        $session = models\Session::init($session_name, $ip);
        $session->user_id = $user_id;
        $session->token = $session_token->token;
        $session_dao->save($session);

        utils\CurrentUser::setSessionToken($session_token->token);

        $users_mailer = new mailers\Users();
        $users_mailer->sendAccountValidationEmail($user, $validation_token);

        $response = Response::redirect('onboarding');
        $response->setCookie('flusio_session_token', $session_token->token, [
            'expires' => $session_token->expired_at->getTimestamp(),
            'samesite' => 'Lax',
        ]);
        return $response;
    }
}
