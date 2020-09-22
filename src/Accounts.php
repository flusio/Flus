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

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        return Response::ok('accounts/show.phtml', [
            'username' => $user->username,
            'locale' => $user->locale,
            'topics' => $topics,
            'topic_ids' => array_column($user->topics(), 'id'),
        ]);
    }

    /**
     * Update the account (current user) info
     *
     * @request_param string csrf
     * @request_param string username
     * @request_param string locale
     * @request_param string[] topic_ids
     *
     * @response 302 /login?redirect_to=/account if the user is not connected
     * @response 400 if the CSRF, username, topic_ids or locale are invalid
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function update($request)
    {
        $user_dao = new models\dao\User();
        $topic_dao = new models\dao\Topic();
        $users_to_topics_dao = new models\dao\UsersToTopics();
        $username = $request->param('username');
        $locale = $request->param('locale');
        $topic_ids = $request->param('topic_ids', []);

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('account'),
            ]);
        }

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('accounts/show.phtml', [
                'username' => $username,
                'locale' => $locale,
                'topics' => $topics,
                'topic_ids' => $topic_ids,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($topic_ids && !$topic_dao->exists($topic_ids)) {
            return Response::badRequest('accounts/show.phtml', [
                'username' => $username,
                'locale' => $locale,
                'topics' => $topics,
                'topic_ids' => $topic_ids,
                'errors' => [
                    'topic_ids' => _('One of the associated topic doesn’t exist.'),
                ],
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
                'topics' => $topics,
                'topic_ids' => $topic_ids,
                'errors' => $errors,
            ]);
        }

        $user_dao->save($user);
        utils\Locale::setCurrentLocale($locale);
        $users_to_topics_dao->set($user->id, $topic_ids);

        return Response::ok('accounts/show.phtml', [
            'username' => $username,
            'locale' => $locale,
            'current_locale' => $locale,
            'topics' => $topics,
            'topic_ids' => $topic_ids,
        ]);
    }

    /**
     * Show the delete form.
     *
     * @response 302 /login?redirect_to=/account/delete if the user is not connected
     * @response 200
     *
     * @return \Minz\Response
     */
    public function showDelete()
    {
        if (!utils\CurrentUser::get()) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show delete account'),
            ]);
        }

        return Response::ok('accounts/delete.phtml');
    }

    /**
     * Delete the current user.
     *
     * @request_param string csrf
     * @request_param string password
     *
     * @response 302 /login?redirect_to=/account/delete if the user is not connected
     * @response 400 if CSRF token or password is wrong
     * @response 400 if trying to delete the demo account if demo is enabled
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
                'redirect_to' => \Minz\Url::for('show delete account'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('accounts/delete.phtml', [
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $password = $request->param('password');
        if (!$current_user->verifyPassword($password)) {
            return Response::badRequest('accounts/delete.phtml', [
                'errors' => [
                    'password_hash' => _('The password is incorrect.'),
                ],
            ]);
        }

        $demo = \Minz\Configuration::$application['demo'];
        if ($demo && $current_user->email === 'demo@flus.io') {
            return Response::badRequest('accounts/delete.phtml', [
                'error' => _('Sorry but you cannot delete the demo account 😉'),
            ]);
        }

        $user_dao = new models\dao\User();
        $user_dao->delete($current_user->id);
        utils\CurrentUser::reset();
        utils\Flash::set('status', 'user_deleted');
        return Response::redirect('login');
    }
}
