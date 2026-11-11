<?php

namespace App\controllers\my;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\images;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Avatar extends BaseController
{
    /**
     * Show the form to edit the current user’s avatar.
     *
     * @response 200
     *    On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function edit(Request $request): Response
    {
        auth\CurrentUser::require();

        $form = new forms\users\EditAvatar();

        return Response::ok('my/avatar/edit.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Set the avatar of the current user
     *
     * @request_param file avatar
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
        $user = auth\CurrentUser::require();

        $form = new forms\users\EditAvatar();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('my/avatar/edit.html.twig', [
                'form' => $form,
            ]);
        }

        $avatar_file = $form->avatar;

        // Cannot be null as $form->validate() is true, which means the
        // $form->avatar attribute is set.
        assert($avatar_file !== null);

        $previous_avatar_filename = $user->avatar_filename;

        $image_storage = new images\Storage();
        $user->avatar_filename = $image_storage->storeFromFile($avatar_file, 'avatars');
        $user->save();

        // The file can be shared with other users (or be the same as the new
        // one), so it is removed only if nobody uses it anymore.
        if (
            $previous_avatar_filename &&
            !models\User::existsBy(['avatar_filename' => $previous_avatar_filename])
        ) {
            $image_storage->remove('avatars', $previous_avatar_filename);
        }

        return Response::redirect('profile', ['id' => $user->id]);
    }
}
