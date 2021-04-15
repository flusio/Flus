<?php

namespace flusio\controllers\my;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * Handle the requests related to the profile.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Profile
{
    /**
     * Show the main profile page.
     *
     * @response 302 /login?redirect_to=/my/profile
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function show()
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('profile'),
            ]);
        }

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        return Response::ok('my/profile/show.phtml', [
            'username' => $user->username,
            'locale' => $user->locale,
            'topics' => $topics,
            'topic_ids' => array_column($user->topics(), 'id'),
        ]);
    }

    /**
     * Update the current user profile info
     *
     * @request_param string csrf
     * @request_param string username
     * @request_param string locale
     * @request_param string[] topic_ids
     *
     * @response 302 /login?redirect_to=/my/profile
     *     If the user is not connected
     * @response 400
     *     If the CSRF, username, topic_ids or locale are invalid
     * @response 200
     */
    public function update($request)
    {
        $users_to_topics_dao = new models\dao\UsersToTopics();
        $username = $request->param('username');
        $locale = $request->param('locale');
        $topic_ids = $request->param('topic_ids', []);

        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('profile'),
            ]);
        }

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('my/profile/show.phtml', [
                'username' => $username,
                'locale' => $locale,
                'topics' => $topics,
                'topic_ids' => $topic_ids,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($topic_ids && !models\Topic::exists($topic_ids)) {
            return Response::badRequest('my/profile/show.phtml', [
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
            return Response::badRequest('my/profile/show.phtml', [
                'username' => $username,
                'locale' => $locale,
                'topics' => $topics,
                'topic_ids' => $topic_ids,
                'errors' => $errors,
            ]);
        }

        $user->save();
        utils\Locale::setCurrentLocale($locale);
        $users_to_topics_dao->set($user->id, $topic_ids);

        return Response::ok('my/profile/show.phtml', [
            'username' => $username,
            'locale' => $locale,
            'current_locale' => $locale,
            'topics' => $topics,
            'topic_ids' => $topic_ids,
        ]);
    }
}
