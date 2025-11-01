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

        return Response::ok('collections/shares/index.phtml', [
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
            return Response::badRequest('collections/shares/index.phtml', [
                'collection' => $collection,
                'form' => $form,
            ]);
        }

        $collection->shareWith($form->user(), $form->type());

        return Response::ok('collections/shares/index.phtml', [
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
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::badRequest('collections/shares/index.phtml', [
                'collection' => $collection,
                'form' => new forms\collections\ShareCollection(options: [
                    'collection' => $collection,
                ]),
            ]);
        }

        $collection->unshareWith($form->user());

        return Response::ok('collections/shares/index.phtml', [
            'collection' => $collection,
            'form' => new forms\collections\ShareCollection(options: [
                'collection' => $collection,
            ]),
        ]);
    }
}
