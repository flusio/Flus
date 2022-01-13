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
     * Show the form to edit the current user’s profile.
     *
     * @response 302 /login?redirect_to=/my/profile
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function edit()
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit profile'),
            ]);
        }

        return Response::ok('my/profile/edit.phtml', [
            'username' => $user->username,
            'locale' => $user->locale,
        ]);
    }

    /**
     * Update the current user profile info
     *
     * @request_param string csrf
     * @request_param string username
     * @request_param string locale
     *
     * @response 302 /login?redirect_to=/my/profile
     *     If the user is not connected
     * @response 400
     *     If the CSRF, username or locale are invalid
     * @response 302 /my/profile
     *     On success
     */
    public function update($request)
    {
        $username = $request->param('username');
        $locale = $request->param('locale');
        $csrf = $request->param('csrf');

        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit profile'),
            ]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest('my/profile/edit.phtml', [
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
            return Response::badRequest('my/profile/edit.phtml', [
                'username' => $username,
                'locale' => $locale,
                'errors' => $errors,
            ]);
        }

        $user->save();
        utils\Locale::setCurrentLocale($locale);

        return Response::redirect('edit profile');
    }
}
