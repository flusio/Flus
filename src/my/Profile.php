<?php

namespace flusio\my;

use Minz\Response;
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
        $user = utils\CurrentUser::get();
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

        $user = utils\CurrentUser::get();
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

    /**
     * Set the avatar of the current user
     *
     * @request_param string csrf
     * @request_param file avatar
     *
     * @response 302 /login?redirect_to=/my/profile
     *     If the user is not connected
     * @response 302
     * @flash error
     *     If the CSRF or avatar are invalid
     * @response 302 /my/profile
     *     On success
     */
    public function updateAvatar($request)
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('profile'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::redirect('profile');
        }

        $uploaded_file = $request->param('avatar');
        $error_status = $uploaded_file['error'];
        if (
            $error_status === UPLOAD_ERR_INI_SIZE ||
            $error_status === UPLOAD_ERR_FORM_SIZE
        ) {
            utils\Flash::set('error', _('This file is too large.'));
            return Response::redirect('profile');
        } elseif ($error_status !== UPLOAD_ERR_OK) {
            utils\Flash::set(
                'error',
                vsprintf(_('This file cannot be uploaded (error %d).'), [$error_status])
            );
            return Response::redirect('profile');
        }

        if (!is_uploaded_file($uploaded_file['tmp_name'])) {
            utils\Flash::set('error', _('This file cannot be uploaded.'));
            return Response::redirect('profile');
        }

        $media_path = \Minz\Configuration::$application['media_path'];
        $avatars_path = "{$media_path}/avatars/";
        if (!file_exists($avatars_path)) {
            @mkdir($avatars_path, 0755, true);
        }

        $image_data = @file_get_contents($uploaded_file['tmp_name']);
        try {
            $image = models\Image::fromString($image_data);
            $image_type = $image->type();
        } catch (\DomainException $e) {
            $image_type = null;
        }

        if ($image_type !== 'png' && $image_type !== 'jpeg') {
            utils\Flash::set('error', _('The photo must be <abbr>PNG</abbr> or <abbr>JPG</abbr>.'));
            return Response::redirect('profile');
        }

        $image->resize(150, 150);

        if ($user->avatar_filename) {
            @unlink($avatars_path . $user->avatar_filename);
        }

        $image_filename = "{$user->id}.{$image_type}";
        $image->save($avatars_path . $image_filename);

        $user->avatar_filename = $image_filename;
        $user->save();

        return Response::redirect('profile');
    }

    /**
     * Return some useful information for the browser extension
     *
     * @response 302 /login?redirect_to=/my/info.json
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function info($request)
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('profile info'),
            ]);
        }

        $bookmarks = $user->bookmarks();
        $links = $bookmarks->links();
        $json_output = json_encode([
            'csrf' => $user->csrf,
            'bookmarks_id' => $bookmarks->id,
            'bookmarked_urls' => array_column($links, 'url'),
        ]);

        $response = Response::text(200, $json_output);
        $response->setHeader('Content-Type', 'application/json');
        return $response;
    }
}
