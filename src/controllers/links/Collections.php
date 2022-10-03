<?php

namespace flusio\controllers\links;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * Handle the requests related to the links collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections
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
    public function index($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $mark_as_read = $request->paramBoolean('mark_as_read', false);
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        $messages = [];
        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $existing_link = models\Link::findBy([
            'user_id' => $user->id,
            'url_lookup' => utils\Belt::removeScheme($link->url),
        ]);
        if ($existing_link) {
            $link = $existing_link;
            $messages = $link->messages();
        }

        if (auth\LinksAccess::canUpdate($user, $link)) {
            $collection_ids = array_column($link->collections(), 'id');
        } else {
            $collection_ids = [];
        }

        $collections = $user->collections();
        utils\Sorter::localeSort($collections, 'name');
        $shared_collections = $user->sharedCollections([], [
            'access_type' => 'write',
        ]);
        utils\Sorter::localeSort($shared_collections, 'name');
        $collections_by_others = models\Collection::daoToList(
            'listWritableContainingNotOwnedLinkWithUrl',
            $user->id,
            $link->url_lookup,
        );
        utils\Sorter::localeSort($collections_by_others, 'name');

        return Response::ok('links/collections/index.phtml', [
            'link' => $link,
            'collection_ids' => $collection_ids,
            'collections' => $collections,
            'shared_collections' => $shared_collections,
            'collections_by_others' => $collections_by_others,
            'mark_as_read' => $mark_as_read,
            'messages' => $messages,
            'comment' => '',
            'from' => $from,
        ]);
    }

    /**
     * Update the link collections list
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string[] collection_ids
     * @request_param boolean is_hidden
     * @request_param boolean mark_as_read
     * @request_param string comment
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     * @response 404 if the link is not found
     * @response 302 :from if CSRF or collection_ids are invalid
     * @response 302 :from
     */
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $new_collection_ids = $request->paramArray('collection_ids', []);
        $is_hidden = $request->paramBoolean('is_hidden', false);
        $mark_as_read = $request->paramBoolean('mark_as_read', false);
        $comment = trim($request->param('comment', ''));
        $from = $request->param('from');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!$user->canWriteCollections($new_collection_ids)) {
            utils\Flash::set('error', _('One of the associated collection doesn’t exist.'));
            return Response::found($from);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        if (!auth\LinksAccess::canUpdate($user, $link)) {
            $link = $user->obtainLink($link);
            utils\ViaHelper::setLinkVia($link, $from);
        }

        $link->is_hidden = $is_hidden;
        $link->save();

        models\LinkToCollection::setCollections($link->id, $new_collection_ids);

        if ($comment) {
            $message = models\Message::init($user->id, $link->id, $comment);
            $message->save();
        }

        if ($mark_as_read) {
            models\LinkToCollection::markAsRead($user, [$link->id]);
        }

        return Response::found($from);
    }
}
