<?php

namespace App\controllers\my;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
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
        $user = auth\CurrentUser::require();

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

        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($user->id);
        $avatars_path = "{$media_path}/avatars";
        $avatar_path = "{$avatars_path}/{$subpath}";
        if (!file_exists($avatar_path)) {
            @mkdir($avatar_path, 0755, true);
        }

        $image_data = $avatar_file->content() ?: '';

        $image = models\Image::fromString($image_data);

        $image->resize(200, 200);

        if ($user->avatar_filename) {
            $subpath = utils\Belt::filenameToSubpath($user->avatar_filename);
            @unlink("{$avatars_path}/{$subpath}/{$user->avatar_filename}");
        }

        $image_filename = "{$user->id}.webp";
        $image->save("{$avatar_path}/{$image_filename}");

        $user->avatar_filename = $image_filename;
        $user->save();

        return Response::redirect('profile', ['id' => $user->id]);
    }
}
