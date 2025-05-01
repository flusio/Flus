<?php

namespace App\controllers\collections;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Images extends BaseController
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
    public function edit(Request $request): Response
    {
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection_id = $request->parameters->getString('id', '');
        $collection = models\Collection::find($collection_id);

        $can_update = $collection && auth\CollectionsAccess::canUpdate($user, $collection);
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
    public function update(Request $request): Response
    {
        $image_file = $request->parameters->getFile('image');
        $collection_id = $request->parameters->getString('id', '');
        $from = $request->parameters->getString('from', '');
        $csrf = $request->parameters->getString('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);

        $can_update = $collection && auth\CollectionsAccess::canUpdate($user, $collection);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        if (!$image_file) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => _('The file is required.'),
            ]);
        }

        if ($image_file->isTooLarge()) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => _('This file is too large.'),
            ]);
        } elseif ($image_file->error) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => vsprintf(_('This file cannot be uploaded (error %d).'), [$image_file->error]),
            ]);
        }

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('collections/images/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($collection->id);
        $card_path = "{$media_path}/cards/{$subpath}/";
        $cover_path = "{$media_path}/covers/{$subpath}/";
        $large_path = "{$media_path}/large/{$subpath}/";
        if (!file_exists($card_path)) {
            @mkdir($card_path, 0755, true);
        }
        if (!file_exists($cover_path)) {
            @mkdir($cover_path, 0755, true);
        }
        if (!file_exists($large_path)) {
            @mkdir($large_path, 0755, true);
        }

        $image_data = $image_file->content();

        $card_image = null;
        $cover_image = null;
        $large_image = null;

        if ($image_data !== false) {
            try {
                $card_image = models\Image::fromString($image_data);
                $cover_image = models\Image::fromString($image_data);
                $large_image = models\Image::fromString($image_data);
            } catch (\DomainException $e) {
            }
        }

        if (!$card_image || !$cover_image || !$large_image) {
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
            @unlink($card_path . $collection->image_filename);
            @unlink($cover_path . $collection->image_filename);
            @unlink($large_path . $collection->image_filename);
        }

        $image_filename = "{$collection->id}.webp";
        $card_image->save($card_path . $image_filename);
        $cover_image->save($cover_path . $image_filename);
        $large_image->save($large_path . $image_filename);

        $collection->image_filename = $image_filename;
        $collection->save();

        return Response::found($from);
    }
}
