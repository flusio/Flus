<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Users
{
    /**
     * Show the registration form.
     *
     * @response 200
     *
     * @return \Minz\Response
     */
    public function registration()
    {
        if (utils\CurrentUser::get()) {
            return Response::redirect('home');
        }

        return Response::ok('users/registration.phtml', [
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
        $csrf = new \Minz\CSRF();

        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('users/registration.phtml', [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        try {
            $user = models\User::init($username, $email, $password);
        } catch (\Minz\Errors\ModelPropertyError $e) {
            return Response::badRequest('users/registration.phtml', [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'errors' => [
                    $e->property() => $this->formatUserError($e),
                ],
            ]);
        }

        if ($user_dao->findBy(['email' => $user->email])) {
            return Response::badRequest('users/registration.phtml', [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'errors' => [
                    'email' => _('An account already exists with this email address.'),
                ],
            ]);
        }

        $token = models\Token::init();
        $token_dao->save($token);

        $user->setProperty('validation_token', $token->token);
        $user_id = $user_dao->save($user);

        utils\CurrentUser::set($user_id);

        $users_mailer = new mailers\Users();
        $users_mailer->sendRegistrationValidationEmail($user, $token);

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
     * @response 404 if the token doesn't exist
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
            return Response::notFound('users/validation.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $token = new models\Token($raw_token);
        if ($token->hasExpired()) {
            return Response::badRequest('users/validation.phtml', [
                'error' => _('The token has expired.'),
            ]);
        }

        $raw_user = $user_dao->findBy(['validation_token' => $token->token]);
        $user = new models\User($raw_user);
        if ($user->validated_at) {
            return Response::redirect('home');
        }

        $user->setProperty('validated_at', \Minz\Time::now());
        $user_dao->save($user);

        return Response::ok('users/validation.phtml');
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
                'error' => _('A security verification failed: you should try again.'),
                'link_to' => $redirect_to,
            ]);
        }

        if ($user->validated_at) {
            // nothing to do, the user is already validated
            return Response::redirect($redirect_to);
        }

        $token = new models\Token($token_dao->find($user->validation_token));
        if ($token->expiresIn(30, 'minutes')) {
            // the token will expire soon, let's regenerate a new one
            $token = models\Token::init();
            $token_dao->save($token);
            $user->setProperty('validation_token', $token->token);
            $user_dao->save($user);
        }

        $users_mailer = new mailers\Users();
        $users_mailer->sendRegistrationValidationEmail($user, $token);

        return Response::redirect($redirect_to, ['status' => 'validation_email_sent']);
    }

    /**
     * @param \Minz\Errors\ModelPropertyError $error
     *
     * @throws \Minz\Errors\ModelPropertyError if the error is not supported
     *
     * @return string
     */
    private function formatUserError($error)
    {
        $property = $error->property();
        $code = $error->getCode();
        if ($property === 'username') {
            if ($code === \Minz\Errors\ModelPropertyError::PROPERTY_REQUIRED) {
                return _('The username is required.');
            } else {
                return _('The username must be less than 50 characters.');
            }
        } elseif ($property === 'email') {
            if ($code === \Minz\Errors\ModelPropertyError::PROPERTY_REQUIRED) {
                return _('The address email is required.');
            } else {
                return _('The address email is invalid.');
            }
        } elseif ($property === 'password_hash') {
            return _('The password is required.');
        } else {
            throw $error; // @codeCoverageIgnore
        }
    }
}
