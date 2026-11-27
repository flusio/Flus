<?php

namespace App\controllers\collections;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

class Shares extends BaseController
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
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'update', $collection);

        models\link_lists\Preloader::for([$collection])->shares();

        return Response::ok('collections/shares/index.html.twig', [
            'collection' => $collection,
            'form' => new forms\collections\ShareCollection(options: [
                'collection' => $collection,
            ]),
        ]);
    }

    /**
     * @request_param string id
     * @request_param string user_id
     * @request_param string type
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
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
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'update', $collection);

        $form = new forms\collections\ShareCollection(options: [
            'collection' => $collection,
        ]);

        $form->handleRequest($request);

        if (!$form->validate()) {
            models\link_lists\Preloader::for([$collection])->shares();

            return Response::badRequest('collections/shares/index.html.twig', [
                'collection' => $collection,
                'form' => $form,
            ]);
        }

        $collection->shareWith($form->user(), $form->type());

        models\link_lists\Preloader::for([$collection])->shares();

        return Response::ok('collections/shares/index.html.twig', [
            'collection' => $collection,
            'form' => new forms\collections\ShareCollection(options: [
                'collection' => $collection,
            ]),
        ]);
    }

    /**
     * Change the access type of a user to the collection.
     *
     * @request_param string id
     * @request_param int share_id
     * @request_param string type
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection or the share doesn't exist, or if the share
     *     doesn't concern the collection.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the collection.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);
        $collection_share = models\CollectionShare::requireFromRequest($request, parameter: 'share_id');

        if ($collection_share->collection_id !== $collection->id) {
            throw new \Minz\Errors\MissingRecordError('The share doesn’t concern this collection.');
        }

        auth\Access::require($user, 'update', $collection);

        $form = new forms\collections\EditCollectionShare(model: $collection_share);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error(implode(' ', $form->errors()));

            models\link_lists\Preloader::for([$collection])->shares();

            return Response::badRequest('collections/shares/index.html.twig', [
                'collection' => $collection,
                'form' => new forms\collections\ShareCollection(options: [
                    'collection' => $collection,
                ]),
            ]);
        }

        $collection_share = $form->model();
        $collection_share->save();

        models\link_lists\Preloader::for([$collection])->shares();

        return Response::ok('collections/shares/index.html.twig', [
            'collection' => $collection,
            'form' => new forms\collections\ShareCollection(options: [
                'collection' => $collection,
            ]),
        ]);
    }

    /**
     * @request_param string id
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
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
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'update', $collection);

        $form = new forms\collections\UnshareCollection();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            models\link_lists\Preloader::for([$collection])->shares();

            return Response::badRequest('collections/shares/index.html.twig', [
                'collection' => $collection,
                'form' => new forms\collections\ShareCollection(options: [
                    'collection' => $collection,
                ]),
            ]);
        }

        $collection->unshareWith($form->user());

        models\link_lists\Preloader::for([$collection])->shares();

        return Response::ok('collections/shares/index.html.twig', [
            'collection' => $collection,
            'form' => new forms\collections\ShareCollection(options: [
                'collection' => $collection,
            ]),
        ]);
    }
}
