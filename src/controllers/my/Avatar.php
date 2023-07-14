<?php

namespace flusio\controllers\my;

use Minz\Request;
use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Avatar
{
    /**
     * Set the avatar of the current user
     *
     * @request_param string csrf
     * @request_param string from
     * @request_param file avatar
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected
     * @response 302 :from
     * @flash error
     *     If the CSRF or avatar are invalid
     * @response 302 :from
     *     On success
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $avatar_file = $request->paramFile('avatar');
        $csrf = $request->param('csrf', '');
        $from = $request->param('from', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        if (!$avatar_file) {
            \Minz\Flash::set('error', _('The file is required.'));
            return Response::found($from);
        }

        if ($avatar_file->isTooLarge()) {
            \Minz\Flash::set('error', _('This file is too large.'));
            return Response::found($from);
        } elseif ($avatar_file->error) {
            $error = $avatar_file->error;
            \Minz\Flash::set(
                'error',
                vsprintf(_('This file cannot be uploaded (error %d).'), [$error])
            );
            return Response::found($from);
        }

        /** @var string */
        $media_path = \Minz\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($user->id);
        $avatars_path = "{$media_path}/avatars";
        $avatar_path = "{$avatars_path}/{$subpath}";
        if (!file_exists($avatar_path)) {
            @mkdir($avatar_path, 0755, true);
        }

        $image_data = $avatar_file->content();

        $image = null;

        if ($image_data !== false) {
            try {
                $image = models\Image::fromString($image_data);
            } catch (\DomainException $e) {
            }
        }

        if (!$image) {
            \Minz\Flash::set('error', _('The photo must be <abbr>PNG</abbr> or <abbr>JPG</abbr>.'));
            return Response::found($from);
        }

        $image->resize(150, 150);

        if ($user->avatar_filename) {
            $subpath = utils\Belt::filenameToSubpath($user->avatar_filename);
            @unlink("{$avatars_path}/{$subpath}/{$user->avatar_filename}");
        }

        $image_filename = "{$user->id}.{$image->type()}";
        $image->save("{$avatar_path}/{$image_filename}");

        $user->avatar_filename = $image_filename;
        $user->save();

        return Response::found($from);
    }
}
