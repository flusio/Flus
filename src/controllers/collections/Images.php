<?php

namespace flusio\controllers\collections;

use Minz\Response;
use flusio\auth;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Images
{
    /**
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn’t exist or is inaccessible
     * @response 200
     *     On success
     */
    public function edit($request)
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from');
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $collection_id = $request->param('id');
        $collection = models\Collection::find($collection_id);

        $can_update = auth\CollectionsAccess::canUpdate($user, $collection);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('collections/images/edit.phtml', [
            'collection' => $collection,
            'from' => $from,
        ]);
    }

    /**
     * @request_param string id
     * @request_param file image
     * @request_param string csrf
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn’t exist or is inaccessible
     * @response 400
     *     If CSRF or file is invalid
     * @response 302 :from
     *     On success
     */
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from');
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $uploaded_file = $request->param('image');
        $collection_id = $request->param('id');

        $collection = models\Collection::find($collection_id);

        $can_update = auth\CollectionsAccess::canUpdate($user, $collection);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        if (!isset($uploaded_file['error'])) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => _('The file is required.'),
            ]);
        }

        $error_status = $uploaded_file['error'];
        if (
            $error_status === UPLOAD_ERR_INI_SIZE ||
            $error_status === UPLOAD_ERR_FORM_SIZE
        ) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => _('This file is too large.'),
            ]);
        } elseif ($error_status !== UPLOAD_ERR_OK) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => vsprintf(_('This file cannot be uploaded (error %d).'), [$error_status]),
            ]);
        }

        if (!is_uploaded_file($uploaded_file['tmp_name'])) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => _('This file cannot be uploaded.'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $media_path = \Minz\Configuration::$application['media_path'];
        $cards_path = "{$media_path}/cards/";
        $covers_path = "{$media_path}/covers/";
        $large_path = "{$media_path}/large/";
        if (!file_exists($cards_path)) {
            @mkdir($cards_path, 0755, true);
        }
        if (!file_exists($covers_path)) {
            @mkdir($covers_path, 0755, true);
        }
        if (!file_exists($large_path)) {
            @mkdir($large_path, 0755, true);
        }

        $image_data = @file_get_contents($uploaded_file['tmp_name']);
        try {
            $card_image = models\Image::fromString($image_data);
            $cover_image = models\Image::fromString($image_data);
            $large_image = models\Image::fromString($image_data);
            $image_type = $card_image->type();
        } catch (\DomainException $e) {
            $image_type = null;
        }

        if ($image_type !== 'png' && $image_type !== 'jpeg') {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => _('The photo must be <abbr>PNG</abbr> or <abbr>JPG</abbr>.'),
            ]);
        }

        $card_image->resize(300, 150);
        $cover_image->resize(300, 300);
        $large_image->resize(1100, 250);

        if ($collection->image_filename) {
            @unlink($cards_path . $collection->image_filename);
            @unlink($covers_path . $collection->image_filename);
            @unlink($large_path . $collection->image_filename);
        }

        $image_filename = "{$collection->id}.{$image_type}";
        $card_image->save($cards_path . $image_filename);
        $cover_image->save($covers_path . $image_filename);
        $large_image->save($large_path . $image_filename);

        $collection->image_filename = $image_filename;
        $collection->save();

        return Response::found($from);
    }
}
