<?php

namespace App\controllers\collections;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

class Filters extends BaseController
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
     *     If the collection doesn't exist or is not followed by the user.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the collection.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'view', $collection);

        $followed_collection = $user->followedCollection($collection->id);
        $form = new forms\collections\EditTimeFilter(model: $followed_collection);

        return Response::ok('collections/filters/edit.phtml', [
            'collection' => $collection,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string time_filter
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
     *     If the collection doesn't exist or is not followed by the user.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the collection.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'view', $collection);

        $followed_collection = $user->followedCollection($collection->id);
        $form = new forms\collections\EditTimeFilter(model: $followed_collection);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('collections/filters/edit.phtml', [
                'collection' => $collection,
                'form' => $form,
            ]);
        }

        $followed_collection = $form->model();
        $followed_collection->save();

        return Response::found(utils\RequestHelper::from($request));
    }
}
