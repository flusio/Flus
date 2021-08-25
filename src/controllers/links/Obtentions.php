<?php

namespace flusio\controllers\links;

use Minz\Response;
use flusio\auth;
use flusio\models;

/**
 * Handle requests to obtain a link from another user.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Obtentions
{
    /**
     * Show the form offering to add the link to collections
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the link is not found or is inaccessible
     * @response 200
     *     on success
     */
    public function new($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $collections = $user->collections();
        models\Collection::sort($collections, $user->locale);

        // $link should be owned by a different user, but the current user can
        // also have a link with the same URL in its collections.
        $existing_link = models\Link::findBy([
            'url' => $link->url,
            'user_id' => $user->id,
        ]);
        if ($existing_link) {
            $is_hidden = $existing_link->is_hidden;
            $existing_collections = $existing_link->collections();
            $collection_ids = array_column($existing_collections, 'id');
        } else {
            $is_hidden = false;
            $collection_ids = [];
        }

        return Response::ok('links/obtentions/new.phtml', [
            'link' => $link,
            'is_hidden' => $is_hidden,
            'collection_ids' => $collection_ids,
            'collections' => $collections,
            'exists_already' => $existing_link !== null,
            'from' => $from,
        ]);
    }

    /**
     * Add the link to the user's collections.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string[] collection_ids
     * @request_param boolean is_hidden
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the link is not found or is inaccessible
     * @response 302 :from
     *     if CSRF or collection_ids are invalid
     * @response 302 :from
     *     on success
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $is_hidden = $request->param('is_hidden', false);
        $collection_ids = $request->param('collection_ids', []);
        $from = $request->param('from');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $collections = $user->collections();
        models\Collection::sort($collections, $user->locale);

        $existing_link = models\Link::findBy([
            'url' => $link->url,
            'user_id' => $user->id,
        ]);

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest('links/obtentions/new.phtml', [
                'link' => $link,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'exists_already' => $existing_link !== null,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if (empty($collection_ids)) {
            return Response::badRequest('links/obtentions/new.phtml', [
                'link' => $link,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'exists_already' => $existing_link !== null,
                'from' => $from,
                'errors' => [
                    'collection_ids' => _('The link must be associated to a collection.'),
                ],
            ]);
        }

        if (!models\Collection::daoCall('existForUser', $user->id, $collection_ids)) {
            return Response::badRequest('links/obtentions/new.phtml', [
                'link' => $link,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'exists_already' => $existing_link !== null,
                'from' => $from,
                'errors' => [
                    'collection_ids' => _('One of the associated collection doesn’t exist.'),
                ],
            ]);
        }

        // First, save the link (if a Link with matching URL exists, just get
        // this link and optionally change its is_hidden status)
        if ($existing_link) {
            $new_link = $existing_link;
        } else {
            $new_link = models\Link::copy($link, $user->id);
        }
        $new_link->is_hidden = filter_var($is_hidden, FILTER_VALIDATE_BOOLEAN);
        $new_link->save();

        // Attach the link to the given collections (and potentially forget the
        // old ones)
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $links_to_collections_dao->set($new_link->id, $collection_ids);

        return Response::found($from);
    }
}
