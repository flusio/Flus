<?php

namespace App\controllers\api\v1\links;

use App\auth;
use App\controllers\api\v1\BaseController;
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
     * @request_param string link_id
     * @request_param string collection_id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot update the link or add links to the collection.
     * @response 404
     *     If the collection or the link do not exist.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $link_id = $request->parameters->getString('link_id', '');
        $collection_id = $request->parameters->getString('collection_id', '');

        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::json(404, [
                'error' => 'The link does not exist.',
            ]);
        }

        $collection = models\Collection::find($collection_id);

        if (!$collection) {
            return Response::json(404, [
                'error' => 'The collection does not exist.',
            ]);
        }

        if (
            !auth\LinksAccess::canUpdate($user, $link) ||
            !auth\CollectionsAccess::canAddLinks($user, $collection)
        ) {
            return Response::json(403, [
                'error' => 'You cannot update the link.',
            ]);
        }

        $link->addCollection($collection);

        return Response::json(200, []);
    }

    /**
     * @request_param string link_id
     * @request_param string collection_id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot update the link or add links to the collection.
     * @response 404
     *     If the collection or the link do not exist.
     * @response 200
     */
    public function delete(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $link_id = $request->parameters->getString('link_id', '');
        $collection_id = $request->parameters->getString('collection_id', '');

        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::json(404, [
                'error' => 'The link does not exist.',
            ]);
        }

        $collection = models\Collection::find($collection_id);

        if (!$collection) {
            return Response::json(404, [
                'error' => 'The collection does not exist.',
            ]);
        }

        if (
            !auth\LinksAccess::canUpdate($user, $link) ||
            !auth\CollectionsAccess::canAddLinks($user, $collection)
        ) {
            return Response::json(403, [
                'error' => 'You cannot update the link.',
            ]);
        }

        $link->removeCollection($collection);

        return Response::json(200, []);
    }
}
