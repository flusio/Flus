<?php

namespace App\controllers\api\v1;

use App\auth;
use App\forms\api as forms;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections extends BaseController
{
    /**
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 200
     */
    public function index(): Response
    {
        $user = auth\CurrentUser::require();

        return Response::json(200, array_map(function (models\Collection $collection) use ($user): array {
            return $collection->toJson(context_user: $user);
        }, $user->collections()));
    }

    /**
     * @json_param string name
     * @json_param string description
     * @json_param boolean is_public
     *
     * @response 400
     *     If a parameter is invalid.
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $json_request = $this->toJsonRequest($request);

        $collection = models\Collection::initCollection($user->id);
        $form = new forms\Collection(model: $collection);
        $form->handleRequest($json_request);

        if (!$form->validate()) {
            return $this->badRequest($form->errors(format: false));
        }

        $collection = $form->model();
        $collection->save();

        return Response::json(200, $collection->toJson(context_user: $user));
    }

    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot access the collection.
     * @response 404
     *     If the collection does not exist.
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $collection_id = $request->parameters->getString('id', '');
        $collection = models\Collection::find($collection_id);

        if (!$collection) {
            return Response::json(404, [
                'error' => 'The collection does not exist.',
            ]);
        }

        if (!auth\CollectionsAccess::canView($user, $collection)) {
            return Response::json(403, [
                'error' => 'You cannot access the collection.',
            ]);
        }

        return Response::json(200, $collection->toJson(context_user: $user));
    }

    /**
     * @request_param string id
     * @json_param string name
     * @json_param string description
     * @json_param boolean is_public
     *
     * @response 400
     *     If a parameter is invalid.
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot update the collection.
     * @response 404
     *     If the collection does not exist.
     * @response 200
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $json_request = $this->toJsonRequest($request);

        $collection_id = $request->parameters->getString('id', '');
        $collection = models\Collection::find($collection_id);

        if (!$collection) {
            return Response::json(404, [
                'error' => 'The collection does not exist.',
            ]);
        }

        if (!auth\CollectionsAccess::canUpdate($user, $collection)) {
            return Response::json(403, [
                'error' => 'You cannot update the collection.',
            ]);
        }

        $form = new forms\Collection(model: $collection);
        $form->handleRequest($json_request);

        if (!$form->validate()) {
            return $this->badRequest($form->errors(format: false));
        }

        $collection = $form->model();
        $collection->save();

        return Response::json(200, $collection->toJson(context_user: $user));
    }

    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot delete the collection.
     * @response 404
     *     If the collection does not exist.
     * @response 200
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $collection_id = $request->parameters->getString('id', '');
        $collection = models\Collection::find($collection_id);

        if (!$collection) {
            return Response::json(404, [
                'error' => 'The collection does not exist.',
            ]);
        }

        if (!auth\CollectionsAccess::canDelete($user, $collection)) {
            return Response::json(403, [
                'error' => 'You cannot delete the collection.',
            ]);
        }

        $collection->remove();

        return Response::json(200, []);
    }
}
