<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the accounts.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Accounts
{
    /**
     * Show the main account page.
     *
     * @response 302 /login?redirect_to=/account if the user is not connected
     * @response 200
     */
    public function show()
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account'),
            ]);
        }

        return Response::ok('accounts/show.phtml', [
            'username' => $user->username,
            'locale' => $user->locale,
        ]);
    }

    /**
     * Update the account (current user) info
     *
     * @request_param string csrf
     * @request_param string username
     * @request_param string locale
     *
     * @response 302 /login?redirect_to=/account if the user is not connected
     * @response 400 if the CSRF, username or locale are invalid
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function update($request)
    {
        $user_dao = new models\dao\User();
        $username = $request->param('username');
        $locale = $request->param('locale');

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('accounts/show.phtml', [
                'username' => $username,
                'locale' => $locale,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $old_username = $user->username;
        $old_locale = $user->locale;

        $user->username = trim($username);
        $user->locale = trim($locale);
        $errors = $user->validate();

        if ($errors) {
            // by keeping the new values, an invalid username could be
            // displayed in the header since the `$user` objects are the same
            // (referenced by the CurrentUser::$instance)
            $user->username = $old_username;
            $user->locale = $old_locale;
            return Response::badRequest('accounts/show.phtml', [
                'username' => $username,
                'locale' => $locale,
                'errors' => $errors,
            ]);
        }

        $user_dao->save($user);
        utils\Locale::setCurrentLocale($locale);

        return Response::ok('accounts/show.phtml', [
            'username' => $username,
            'locale' => $locale,
            'current_locale' => $locale,
        ]);
    }

    /**
     * Show the deletion form.
     *
     * @response 302 /login?redirect_to=/account/deletion if the user is not connected
     * @response 200
     *
     * @return \Minz\Response
     */
    public function deletion()
    {
        if (!utils\CurrentUser::get()) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account deletion'),
            ]);
        }

        return Response::ok('accounts/deletion.phtml');
    }

    /**
     * Delete the current user.
     *
     * @request_param string csrf
     * @request_param string password
     *
     * @response 302 /login?redirect_to=/account/deletion if the user is not connected
     * @response 400 if CSRF token or password is wrong
     * @response 302 /login
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
                'redirect_to' => \Minz\Url::for('account deletion'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('accounts/deletion.phtml', [
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $password = $request->param('password');
        if (!$current_user->verifyPassword($password)) {
            return Response::badRequest('accounts/deletion.phtml', [
                'errors' => [
                    'password_hash' => _('The password is incorrect.'),
                ],
            ]);
        }

        $user_dao = new models\dao\User();
        $user_dao->delete($current_user->id);
        utils\CurrentUser::reset();
        utils\Flash::set('status', 'user_deleted');
        return Response::redirect('login');
    }
}
