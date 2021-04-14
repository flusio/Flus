<?php

namespace flusio\controllers\news;

use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Preferences
{
    /**
     * Show the news preferences page.
     *
     * @response 302 /login?redirect_to=/news/preferences
     *     if not connected
     * @response 200
     */
    public function show()
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news preferences'),
            ]);
        }

        $preferences = models\NewsPreferences::fromJson($user->news_preferences);
        return Response::ok('news/preferences/show.phtml', [
            'min_duration' => models\NewsPreferences::MIN_DURATION,
            'max_duration' => models\NewsPreferences::MAX_DURATION,
            'duration' => $preferences->duration,
            'from_bookmarks' => $preferences->from_bookmarks,
            'from_followed' => $preferences->from_followed,
            'from_topics' => $preferences->from_topics,
        ]);
    }

    /**
     * Save the news preferences.
     *
     * @request_param string csrf
     * @request_param integer duration
     * @request_param boolean from_bookmarks
     * @request_param boolean from_followed
     * @request_param boolean from_topics
     *
     * @response 302 /login?redirect_to=/news/preferences
     *     if not connected
     * @response 400
     *     if CSRF or the duration is invalid, or if no "from" option is selected
     * @response 302 /news
     *     on success
     */
    public function update($request)
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('news preferences'),
            ]);
        }

        $duration = $request->param('duration');
        $from_bookmarks = $request->param('from_bookmarks');
        $from_followed = $request->param('from_followed');
        $from_topics = $request->param('from_topics');

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('news/preferences/show.phtml', [
                'min_duration' => models\NewsPreferences::MIN_DURATION,
                'max_duration' => models\NewsPreferences::MAX_DURATION,
                'duration' => $duration,
                'from_bookmarks' => $from_bookmarks,
                'from_followed' => $from_followed,
                'from_topics' => $from_topics,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $preferences = models\NewsPreferences::init(
            $duration,
            $from_bookmarks,
            $from_followed,
            $from_topics
        );

        $errors = $preferences->validate();
        if ($errors) {
            return Response::badRequest('news/preferences/show.phtml', [
                'min_duration' => models\NewsPreferences::MIN_DURATION,
                'max_duration' => models\NewsPreferences::MAX_DURATION,
                'duration' => $duration,
                'from_bookmarks' => $from_bookmarks,
                'from_followed' => $from_followed,
                'from_topics' => $from_topics,
                'errors' => $errors,
            ]);
        }

        $user->news_preferences = $preferences->toJson();
        $user->save();

        return Response::redirect('news');
    }
}
