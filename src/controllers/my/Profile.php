<?php

namespace App\controllers\my;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;

/**
 * Handle the requests related to the profile.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Profile extends BaseController
{
    /**
     * Show the form to edit the current user’s profile.
     *
     * @request_param string from (default is /my/profile)
     *
     * @response 302 /login?redirect_to=:from
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function edit(Request $request): Response
    {
        $from = $request->parameters->getString('from', \Minz\Url::for('edit profile'));

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        return Response::ok('my/profile/edit.phtml', [
            'username' => $user->username,
            'from' => $from,
        ]);
    }

    /**
     * Update the current user profile info
     *
     * @request_param string csrf
     * @request_param string username
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected
     * @response 400
     *     If the CSRF or username are invalid
     * @response 302 :from
     *     On success
     */
    public function update(Request $request): Response
    {
        $username = $request->parameters->getString('username', '');
        $csrf = $request->parameters->getString('csrf', '');
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('my/profile/edit.phtml', [
                'username' => $username,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $old_username = $user->username;
        $user->username = trim($username);

        if (!$user->validate()) {
            // by keeping the new values, an invalid username could be
            // displayed in the header since the `$user` objects are the same
            // (referenced by the CurrentUser::$instance)
            $user->username = $old_username;
            return Response::badRequest('my/profile/edit.phtml', [
                'username' => $username,
                'from' => $from,
                'errors' => $user->errors(),
            ]);
        }

        $user->save();

        return Response::found($from);
    }
}
