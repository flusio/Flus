<?php

namespace App\controllers\collections;

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
class Images extends BaseController
{
    /**
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the collection.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'update', $collection);

        $form = new forms\collections\EditCollectionImage();

        return Response::ok('collections/images/edit.html.twig', [
            'collection' => $collection,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param file image
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the collection.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'update', $collection);

        $form = new forms\collections\EditCollectionImage();

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('collections/images/edit.html.twig', [
                'collection' => $collection,
                'form' => $form,
            ]);
        }

        $image_file = $form->image;

        // Cannot be null as $form->validate() is true, which means the
        // $form->image attribute is set.
        assert($image_file !== null);

        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($collection->id);
        $path_covers = "{$media_path}/covers/{$subpath}/";
        if (!file_exists($path_covers)) {
            @mkdir($path_covers, 0755, true);
        }

        $image_data = $image_file->content() ?: '';

        $cover_image = models\Image::fromString($image_data);
        $cover_image->resize(400, 400);

        if ($collection->image_filename) {
            @unlink($path_covers . $collection->image_filename);
        }

        $image_filename = "{$collection->id}.webp";
        $cover_image->save($path_covers . $image_filename);

        $collection->image_filename = $image_filename;
        $collection->save();

        return Response::found(utils\RequestHelper::from($request));
    }
}
