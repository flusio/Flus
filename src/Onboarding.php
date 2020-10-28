<?php

namespace flusio;

use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Onboarding
{
    /**
     * Show an onboarding page.
     *
     * @request_param integer step
     *
     * @response 302 /login?redirect_to if not connected
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function show($request)
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('onboarding'),
            ]);
        }

        $step = intval($request->param('step', 1));
        if ($step < 1 || $step > 5) {
            return Response::notFound('not_found.phtml');
        }

        // Topics are used during step 4
        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        $user_topics = $user->topics();
        models\Topic::sort($user_topics, $user->locale);
        $user_topic_labels = array_map(function ($topic) {
            return vsprintf(_('“%s”'), $topic->label);
        }, $user_topics);

        return Response::ok("onboarding/step{$step}.phtml", [
            'topics' => $topics,
            'user_topic_labels' => $user_topic_labels,
        ]);
    }

    /**
     * Update the locale of the current user
     *
     * @request_param string csrf
     * @request_param string locale
     *
     * @response 302 /login?redirect_to=/onboarding
     *     if the user is not connected
     * @response 302 /onboarding
     *     if the CSRF or locale are invalid
     * @response 302 /onboarding
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function updateLocale($request)
    {
        $user_dao = new models\dao\User();
        $csrf = new \Minz\CSRF();
        $locale = $request->param('locale');

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('onboarding'),
            ]);
        }

        $user->locale = trim($locale);

        $errors = $user->validate();
        if ($csrf->validateToken($request->param('csrf')) && !$errors) {
            $user_dao->save($user);
            utils\Locale::setCurrentLocale($locale);
        }

        return Response::redirect('onboarding');
    }

    /**
     * Update the topics of the current user
     *
     * @request_param string csrf
     * @request_param string[] topic_ids
     *
     * @response 302 /login?redirect_to=/onboarding?step=4
     *     if the user is not connected
     * @response 302 /onboarding?step=4
     *     if the CSRF or topic_ids are invalid
     * @response 302 /onboarding?step=4
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function updateTopics($request)
    {
        $user_dao = new models\dao\User();
        $topic_ids = $request->param('topic_ids', []);

        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('onboarding', ['step' => 4]),
            ]);
        }

        $csrf = new \Minz\CSRF();
        $topic_dao = new models\dao\Topic();
        $csrf_valid = $csrf->validateToken($request->param('csrf'));
        $topics_valid = !$topic_ids || $topic_dao->exists($topic_ids);

        if (!$csrf_valid || !$topics_valid) {
            return Response::redirect('onboarding', ['step' => 4]);
        }

        $users_to_topics_dao = new models\dao\UsersToTopics();
        $users_to_topics_dao->set($user->id, $topic_ids);

        return Response::redirect('onboarding', ['step' => 5]);
    }
}
