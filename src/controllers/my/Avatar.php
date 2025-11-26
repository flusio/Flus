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

        $form_profile = new forms\users\Profile(model: $user);

        $form_avatar = new forms\users\EditAvatar();
        $form_avatar->handleRequest($request);

        if (!$form_avatar->validate()) {
            return Response::badRequest('my/profile/edit.html.twig', [
                'form_profile' => $form_profile,
                'form_avatar' => $form_avatar,
            ]);
        }

        $avatar_file = $form_avatar->avatar;

        // Cannot be null as $form->validate() is true, which means the
        // $form->image attribute is set.
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

        $image->resize(150, 150);

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
