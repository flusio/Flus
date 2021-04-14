<?php

namespace flusio\controllers\my;

use Minz\Response;
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
}
