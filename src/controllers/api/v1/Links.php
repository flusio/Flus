<?php

namespace App\controllers\api\v1;

use App\auth;
use App\forms\api as forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Links extends BaseController
{
    /**
     * @request_param string collection
     * @request_param integer page
     * @request_param integer per_page
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot access the collection.
     * @response 404
     *     If the collection does not exist.
     * @response 200
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $collection_id = $request->parameters->getString('collection');
        $pagination_page = $request->parameters->getInteger('page', 1);
        $pagination_per_page = $request->parameters->getInteger('per_page', 30);
        $pagination_per_page = min(100, max(1, $pagination_per_page));

        $collection = null;
        if ($collection_id === 'to-read') {
            $collection = $user->bookmarks();
        } elseif ($collection_id === 'read') {
            $collection = $user->readList();
        } elseif ($collection_id) {
            $collection = models\Collection::find($collection_id);
        }

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

        $can_view_hidden_links = auth\CollectionsAccess::canViewHiddenLinks($user, $collection);

        $number_links = models\Link::countByCollectionId($collection->id, [
            'hidden' => $can_view_hidden_links,
        ]);
        $pagination = new utils\Pagination($number_links, $pagination_per_page, $pagination_page);

        $links = $collection->links(
            ['published_at', 'number_notes'],
            [
                'hidden' => $can_view_hidden_links,
                'offset' => $pagination->currentOffset(),
                'limit' => $pagination->numberPerPage(),
            ]
        );

        return Response::json(200, array_map(function (models\Link $link) use ($user): array {
            return $link->toJson(context_user: $user);
        }, $links));
    }

    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot access the link.
     * @response 404
     *     If the link does not exist.
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $link_id = $request->parameters->getString('id', '');
        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::json(404, [
                'error' => 'The link does not exist.',
            ]);
        }

        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::json(403, [
                'error' => 'You cannot access the link.',
            ]);
        }

        return Response::json(200, $link->toJson(context_user: $user));
    }

    /**
     * @request_param string id
     * @json_param string title
     * @json_param integer reading_time
     *
     * @response 400
     *     If a parameter is invalid.
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot update the link.
     * @response 404
     *     If the link does not exist.
     * @response 200
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $json_request = $this->toJsonRequest($request);

        $link_id = $request->parameters->getString('id', '');
        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::json(404, [
                'error' => 'The link does not exist.',
            ]);
        }

        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::json(403, [
                'error' => 'You cannot update the link.',
            ]);
        }

        $form = new forms\Link(model: $link);
        $form->handleRequest($json_request);

        if (!$form->validate()) {
            return $this->badRequest($form->errors(format: false));
        }

        $link = $form->model();
        $link->save();

        return Response::json(200, $link->toJson(context_user: $user));
    }

    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot delete the link.
     * @response 404
     *     If the link does not exist.
     * @response 200
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $link_id = $request->parameters->getString('id', '');
        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::json(404, [
                'error' => 'The link does not exist.',
            ]);
        }

        if (!auth\LinksAccess::canDelete($user, $link)) {
            return Response::json(403, [
                'error' => 'You cannot delete the link.',
            ]);
        }

        $link->remove();

        return Response::json(200, []);
    }
}
