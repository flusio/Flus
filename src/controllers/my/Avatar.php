<?php

namespace flusio\controllers\my;

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
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $avatar_file = $request->paramFile('avatar');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('profile'),
            ]);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::redirect('profile');
        }

        if (!$avatar_file) {
            utils\Flash::set('error', _('The file is required.'));
            return Response::redirect('profile');
        }

        if ($avatar_file->isTooLarge()) {
            utils\Flash::set('error', _('This file is too large.'));
            return Response::redirect('profile');
        } elseif ($avatar_file->error) {
            $error = $avatar_file->error;
            utils\Flash::set(
                'error',
                vsprintf(_('This file cannot be uploaded (error %d).'), [$error])
            );
            return Response::redirect('profile');
        }

        $media_path = \Minz\Configuration::$application['media_path'];
        $avatars_path = "{$media_path}/avatars/";
        if (!file_exists($avatars_path)) {
            @mkdir($avatars_path, 0755, true);
        }

        $image_data = $avatar_file->content();
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
}
