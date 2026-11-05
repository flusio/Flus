<?php

namespace App\controllers\links;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\services;
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
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the link.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'update', $link);

        $form = new forms\links\EditLinkImage();

        return Response::ok('links/images/edit.html.twig', [
            'link' => $link,
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
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the link.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'update', $link);

        $form = new forms\links\EditLinkImage();

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/images/edit.html.twig', [
                'link' => $link,
                'form' => $form,
            ]);
        }

        $image_file = $form->image;

        // Cannot be null as $form->validate() is true, which means the
        // $form->image attribute is set.
        assert($image_file !== null);

        $image_service = new services\Image();
        $link->image_filename = $image_service->storeFromFile($image_file, 'covers');
        $link->save();

        return Response::found(utils\RequestHelper::from($request));
    }
}
