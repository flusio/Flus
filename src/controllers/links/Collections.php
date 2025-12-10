<?php

namespace App\controllers\links;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * Handle the requests related to the links collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections extends BaseController
{
    /**
     * Show the page to update the link collections
     *
     * @request_param string id
     * @request_param boolean mark_as_read
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the link.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'view', $link);

        // Make sure that if the user has already saved the link's URL, we work
        // with this one instead of a link potentially owned by another user.
        // In particular, this allows to be sure that the collections are
        // correctly selected.
        $existing_link = $user->correspondingOwnedLink($link);
        if ($existing_link) {
            $link = $existing_link;
        }

        if (auth\LinksAccess::canUpdate($user, $link)) {
            $collection_ids = array_column($link->collections(), 'id');
        } else {
            $collection_ids = [];
        }

        $mark_as_read = $request->parameters->getBoolean('mark_as_read');

        $form = new forms\links\EditLinkCollections([
            'collection_ids' => $collection_ids,
            'mark_as_read' => $mark_as_read,
        ], $link, [
            'user' => $user,
        ]);

        return Response::ok('links/collections/index.phtml', [
            'link' => $link,
            'form' => $form,
        ]);
    }

    /**
     * Update the link collections list
     *
     * @request_param string id
     * @request_param string[] collection_ids
     * @request_param string[] new_collection_names
     * @request_param boolean is_hidden
     * @request_param boolean mark_as_read
     * @request_param string content
     * @request_param string share_on_mastodon
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
     *     If the user cannot view the link.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'view', $link);

        $from = utils\RequestHelper::from($request);

        if (!auth\LinksAccess::canUpdate($user, $link)) {
            $link = $user->obtainLink($link);
            $link->setSourceFrom($from);
        }

        $form = new forms\links\EditLinkCollections(model: $link, options: [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/collections/index.phtml', [
                'link' => $link,
                'form' => $form,
            ]);
        }

        $link = $form->model();
        $link->save();

        $link_collections = $form->selectedCollections();
        foreach ($form->newCollections() as $collection) {
            $collection->save();
            $link_collections[] = $collection;
        }

        $link->setCollections($link_collections);

        $note = $form->note();
        if ($note) {
            $note->save();
        }

        $link->refreshTags();

        if ($form->mark_as_read) {
            $user->markAsRead($link);
        }

        return Response::found($from);
    }
}
