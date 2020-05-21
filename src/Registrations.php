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
     * @response 200
     *
     * @return \Minz\Response
     */
    public function new()
    {
        if (utils\CurrentUser::get()) {
            return Response::redirect('home');
        }

        return Response::ok('registrations/new.phtml', [
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
     * @response 302 /
     * @response 400 if CSRF token is wrong
     * @response 400 if email, username or password is missing/invalid
     * @response 400 if email already exists
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

        $username = $request->param('username');
        $email = $request->param('email');
        $password = $request->param('password');
        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();
        $session_dao = new models\dao\Session();
        $csrf = new \Minz\CSRF();

        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('registrations/new.phtml', [
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
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'errors' => $this->formatUserErrors($errors),
            ]);
        }

        if ($user_dao->findBy(['email' => $user->email])) {
            return Response::badRequest('registrations/new.phtml', [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'errors' => [
                    'email' => _('An account already exists with this email address.'),
                ],
            ]);
        }

        $validation_token = models\Token::init(1, 'day', 8);
        $token_dao->save($validation_token);

        $user->validation_token = $validation_token->token;
        $user_id = $user_dao->save($user);

        // Initialize the current session
        $session_token = models\Token::init(1, 'month');
        $token_dao->save($session_token);

        $session_name = utils\Browser::format($request->header('HTTP_USER_AGENT', ''));
        $ip = $request->header('REMOTE_ADDR', 'unknown');
        $session = models\Session::init($session_name, $ip);
        $session->user_id = $user_id;
        $session->token = $session_token->token;
        $session_dao->save($session);

        utils\CurrentUser::set($user_id);

        $users_mailer = new mailers\Users();
        $users_mailer->sendRegistrationValidationEmail($user, $validation_token);

        return Response::redirect('home');
    }

    /**
     * Validate a registration.
     *
     * @request_param string t The registration validation token
     *
     * @response 200 if the token is valid and the registration validated
     * @response 302 / if the token is valid and the registration already validated
     * @response 400 if the token has expired
     * @response 404 if the token doesn't exist or not associated to a User
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function validation($request)
    {
        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();

        $raw_token = $token_dao->find($request->param('t'));
        if (!$raw_token) {
            return Response::notFound('registrations/validation.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $token = new models\Token($raw_token);
        if (!$token->isValid()) {
            return Response::badRequest('registrations/validation.phtml', [
                'error' => _('The token has expired or has been invalidated.'),
            ]);
        }

        $raw_user = $user_dao->findBy(['validation_token' => $token->token]);
        if (!$raw_user) {
            return Response::notFound('registrations/validation.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $user = new models\User($raw_user);

        // No need to keep the token in database, whether or not the user is
        // already validated.
        $token_dao->delete($token->token);
        $user->validation_token = null;

        if ($user->validated_at) {
            return Response::redirect('home');
        }

        $user->validated_at = \Minz\Time::now();
        $user_dao->save($user);

        return Response::ok('registrations/validation.phtml');
    }

    /**
     * Resend a registration validation email.
     *
     * A new token is generated if the current one expires soon (i.e. <= 30
     * minutes).
     *
     * @request_param string csrf
     * @request_param string redirect_to (default: home)
     *
     * @response 302 $redirect_to?status=validation_email_sent
     * @response 302 $redirect_to if the user was already validated
     * @response 400 if CSRF token is wrong
     * @response 401 if the user is not connected
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function resendValidationEmail($request)
    {
        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();
        $redirect_to = $request->param('redirect_to', 'home');
        $csrf = new \Minz\CSRF();
        $user = utils\CurrentUser::get();

        if (!$user) {
            return Response::unauthorized('unauthorized.phtml', [
                'link_to' => $redirect_to,
            ]);
        }

        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('bad_request.phtml', [
                'error' => _('A security verification failed: you should retry to submit the form.'),
                'link_to' => $redirect_to,
            ]);
        }

        if ($user->validated_at) {
            // nothing to do, the user is already validated
            return Response::redirect($redirect_to);
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

        return Response::redirect($redirect_to, ['status' => 'validation_email_sent']);
    }

    /**
     * @param array $errors
     *
     * @return array
     */
    private function formatUserErrors($errors)
    {
        $formatted_errors = [];
        foreach ($errors as $property => $error) {
            $code = $error['code'];
            if ($property === 'username') {
                if ($code === \Minz\Model::ERROR_REQUIRED) {
                    $formatted_error = _('The username is required.');
                } else {
                    $formatted_error = _('The username must be less than 50 characters.');
                }
            } elseif ($property === 'email') {
                if ($code === \Minz\Model::ERROR_REQUIRED) {
                    $formatted_error = _('The address email is required.');
                } else {
                    $formatted_error = _('The address email is invalid.');
                }
            } elseif ($property === 'password_hash') {
                $formatted_error = _('The password is required.');
            } else {
                $formatted_error = $error; // @codeCoverageIgnore
            }

            $formatted_errors[$property] = $formatted_error;
        }
        return $formatted_errors;
    }
}
