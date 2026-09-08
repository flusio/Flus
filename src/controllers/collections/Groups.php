<?php

namespace App\controllers\collections;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

class Groups extends BaseController
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
     *     If the user cannot update the collection group.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'updateGroup', $collection);

        $group = $collection->group();

        $form = new forms\collections\EditCollectionGroup([
            'name' => $group ? $group->name : '',
        ], options: [
            'user' => $user,
        ]);

        return Response::ok('collections/groups/edit.html.twig', [
            'collection' => $collection,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string name
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
     *     If the user cannot update the collection group.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'updateGroup', $collection);

        $form = new forms\collections\EditCollectionGroup(options: [
            'user' => $user,
        ]);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('collections/groups/edit.html.twig', [
                'collection' => $collection,
                'form' => $form,
            ]);
        }

        $group = $form->group();

        if ($group && !$group->isPersisted()) {
            $group->save();
        }

        $collection->group_id = $group?->id;
        $collection->save();

        return Response::found(utils\RequestHelper::from($request));
    }
}
