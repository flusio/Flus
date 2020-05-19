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

        $user = models\User::init($username, $email, $password);
        $user->locale = utils\Locale::currentLocale();

        $errors = $user->validate();
        if ($errors) {
            return Response::badRequest('users/registration.phtml', [
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'errors' => $this->formatUserErrors($errors),
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

        $user->validation_token = $token->token;
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
            return Response::notFound('users/validation.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $token = new models\Token($raw_token);
        if (!$token->isValid()) {
            return Response::badRequest('users/validation.phtml', [
                'error' => _('The token has expired or has been invalidated.'),
            ]);
        }

        $raw_user = $user_dao->findBy(['validation_token' => $token->token]);
        if (!$raw_user) {
            return Response::notFound('users/validation.phtml', [
                'error' => _('The token doesn’t exist.'),
            ]);
        }

        $user = new models\User($raw_user);
        if ($user->validated_at) {
            return Response::redirect('home');
        }

        $user->validated_at = \Minz\Time::now();
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
                'error' => _('A security verification failed: you should retry to submit the form.'),
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
            $user->validation_token = $token->token;
            $user_dao->save($user);
        }

        $users_mailer = new mailers\Users();
        $users_mailer->sendRegistrationValidationEmail($user, $token);

        return Response::redirect($redirect_to, ['status' => 'validation_email_sent']);
    }

    /**
     * Show the deletion form.
     *
     * @response 200
     * @response 302 /login?redirect_to=/settings/deletion if the user is not connected
     *
     * @return \Minz\Response
     */
    public function deletion()
    {
        if (!utils\CurrentUser::get()) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('user deletion'),
            ]);
        }

        return Response::ok('users/deletion.phtml');
    }

    /**
     * Delete the current user.
     *
     * @request_param string csrf
     * @request_param string password
     *
     * @response 302 /
     * @response 302 /login?redirect_to=/settings/deletion if the user is not connected
     * @response 400 if CSRF token or password is wrong
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function delete($request)
    {
        $current_user = utils\CurrentUser::get();
        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('user deletion'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('users/deletion.phtml', [
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $password = $request->param('password');
        if (!$current_user->verifyPassword($password)) {
            return Response::badRequest('users/deletion.phtml', [
                'errors' => [
                    'password_hash' => _('The password is incorrect.'),
                ],
            ]);
        }

        $user_dao = new models\dao\User();
        $user_dao->delete($current_user->id);
        utils\CurrentUser::reset();
        return Response::redirect('home', ['status' => 'user_deleted']);
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
