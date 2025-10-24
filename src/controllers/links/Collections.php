<?php

namespace App\controllers\links;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\jobs;
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
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     * @response 404 if the link is not found
     * @response 200
     */
    public function index(Request $request): Response
    {
        $link_id = $request->parameters->getString('id', '');
        $mark_as_read = $request->parameters->getBoolean('mark_as_read');
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = models\Link::find($link_id);

        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

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

        $form = new forms\links\EditLinkCollections([
            'collection_ids' => $collection_ids,
            'mark_as_read' => $mark_as_read,
        ], $link);

        return Response::ok('links/collections/index.phtml', [
            'link' => $link,
            'form' => $form,
            'from' => $from,
        ]);
    }

    /**
     * Update the link collections list
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string[] collection_ids
     * @request_param string[] new_collection_names
     * @request_param boolean is_hidden
     * @request_param boolean mark_as_read
     * @request_param string content
     * @request_param string share_on_mastodon
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected.
     * @response 404
     *     If the link is not found.
     * @response 302 :from
     *     If CSRF, collection_ids or new_collection_names are invalid.
     * @response 302 :from
     *     On success.
     */
    public function update(Request $request): Response
    {
        $link_id = $request->parameters->getString('id', '');
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!auth\LinksAccess::canUpdate($user, $link)) {
            $link = $user->obtainLink($link);
            utils\SourceHelper::setLinkSource($link, $from);
        }

        $form = new forms\links\EditLinkCollections(model: $link);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/collections/index.phtml', [
                'link' => $link,
                'form' => $form,
                'from' => $from,
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

        if ($form->shouldShareOnMastodon()) {
            $share_on_mastodon_job = new jobs\ShareOnMastodon();
            $share_on_mastodon_job->performAsap($user->id, $link->id, $note?->id);
        }

        return Response::found($from);
    }
}
