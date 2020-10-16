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

        $validation_token = models\Token::init(1, 'day', 8);
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
        $users_mailer->sendRegistrationValidationEmail($user, $validation_token);

        $response = Response::redirect('onboarding');
        $response->setCookie('flusio_session_token', $session_token->token, [
            'expires' => $session_token->expired_at->getTimestamp(),
            'samesite' => 'Lax',
        ]);
        return $response;
    }

    /**
     * Validate a registration.
     *
     * @request_param string t The registration validation token
     *
     * @response 302 /login?redirect_to=/registrations/validation if no token and current user is not connected
     * @response 302 / if no token and current user is already validated
     * @response 200 if no token
     *
     * @response 404 if the token doesn't exist
     * @response 400 if the token has expired
     * @response 404 if the token is not associated to a User
     * @response 302 / if the token is valid and the registration already validated
     * @response 200 if the registration has been validated
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function validation($request)
    {
        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();

        $token = $request->param('t');
        $current_user = utils\CurrentUser::get();
        if (!$token) {
            if (!$current_user) {
                return Response::redirect('login', [
                    'redirect_to' => \Minz\Url::for('registration validation'),
                ]);
            } elseif ($current_user->validated_at) {
                return Response::redirect('home');
            } else {
                return Response::ok('registrations/validation.phtml');
            }
        }

        $db_token = $token_dao->find($token);
        if (!$db_token) {
            return Response::notFound('registrations/validation.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $token = new models\Token($db_token);
        if (!$token->isValid()) {
            return Response::badRequest('registrations/validation.phtml', [
                'error' => _('The token has expired or has been invalidated.'),
            ]);
        }

        $db_user = $user_dao->findBy(['validation_token' => $token->token]);
        if (!$db_user) {
            return Response::notFound('registrations/validation.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $user = new models\User($db_user);

        // No need to keep the token in database, whether or not the user is
        // already validated.
        $token_dao->delete($token->token);
        $user->validation_token = null;

        if ($user->validated_at) {
            return Response::redirect('home');
        }

        $user->validated_at = \Minz\Time::now();

        $app_conf = \Minz\Configuration::$application;
        if ($app_conf['subscriptions_enabled']) {
            $subscriptions_service = new services\Subscriptions(
                $app_conf['subscriptions_host'],
                $app_conf['subscriptions_private_key']
            );
            $account = $subscriptions_service->account($user->email);
            if ($account) {
                $user->subscription_account_id = $account['id'];
                $user->subscription_expired_at = date_create_from_format(
                    \Minz\Model::DATETIME_FORMAT,
                    $account['expired_at']
                );
            } else {
                \Minz\Log::error("Can’t get a subscription account for user {$user->id}."); // @codeCoverageIgnore
            }
        }

        $user_dao->save($user);

        return Response::ok('registrations/validation.phtml', [
            'success' => true,
        ]);
    }

    /**
     * Resend a registration validation email.
     *
     * A new token is generated if the current one expires soon (i.e. <= 30
     * minutes).
     *
     * @request_param string csrf
     * @request_param string from default: /
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 302 :from
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function resendValidationEmail($request)
    {
        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();
        $from = $request->param('from', \Minz\Url::for('home'));
        $csrf = new \Minz\CSRF();
        $user = utils\CurrentUser::get();

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::found($from);
        }

        if ($user->validated_at) {
            // nothing to do, the user is already validated
            return Response::found($from);
        }

        $token = new models\Token($token_dao->find($user->validation_token));
        if ($token->expiresIn(30, 'minutes') || $token->isInvalidated()) {
            // the token will expire soon, let's regenerate a new one
            $token = models\Token::init(1, 'day', 8);
            $token_dao->save($token);
            $user->validation_token = $token->token;
            $user_dao->save($user);
        }

        $users_mailer = new mailers\Users();
        $users_mailer->sendRegistrationValidationEmail($user, $token);

        utils\Flash::set('status', 'validation_email_sent');
        return Response::found($from);
    }
}
