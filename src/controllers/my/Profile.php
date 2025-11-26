<?php

namespace App\controllers\my;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use Minz\Request;
use Minz\Response;

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
     * @response 200
     *    On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form_profile = new forms\users\Profile(model: $user);
        $form_avatar = new forms\users\EditAvatar();

        return Response::ok('my/profile/edit.html.twig', [
            'form_profile' => $form_profile,
            'form_avatar' => $form_avatar,
        ]);
    }

    /**
     * Update the current user profile info.
     *
     * @request_param string username
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /profile/:id
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function update(Request $request): Response
    {
        $current_user = auth\CurrentUser::require();

        $form_avatar = new forms\users\EditAvatar();

        $form_profile = new forms\users\Profile(model: $current_user);
        $form_profile->handleRequest($request);

        if (!$form_profile->validate()) {
            return Response::badRequest('my/profile/edit.html.twig', [
                'form_profile' => $form_profile,
                'form_avatar' => $form_avatar,
            ]);
        }

        $user = $form_profile->model();
        $user->save();

        return Response::redirect('profile', ['id' => $user->id]);
    }
}
