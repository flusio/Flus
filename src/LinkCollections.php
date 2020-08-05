<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the links collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkCollections
{
    /**
     * Show the page to update the link collections
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/:id/collections
     * @response 404 if the link is not found
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function index($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('link collections', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        $collections = $user->collections();
        $link_collection_ids = array_column($link->collections(), 'id');
        foreach ($collections as $collection) {
            if (in_array($collection->id, $link_collection_ids)) {
                $collection->attachedToLink = true;
            } else {
                $collection->attachedToLink = false;
            }
        }

        models\Collection::sort($collections, $user->locale);

        return Response::ok('link_collections/index.phtml', [
            'link' => $link,
            'collections' => $collections,
        ]);
    }

    /**
     * Update the link collections list
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string[] collections
     *
     * @response 302 /login?redirect_to=/links/:id/collections
     * @response 404 if the link is not found
     * @response 400 if csrf token is invalid
     * @response 302 /links/:id
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function update($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');
        $new_collection_ids = $request->param('collection_ids', []);

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('link collections', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $old_collection_ids = array_column($link->collections(), 'id');

        $ids_to_attach = array_diff($new_collection_ids, $old_collection_ids);
        if ($ids_to_attach) {
            $links_to_collections_dao->attach($link->id, $ids_to_attach);
        }

        $ids_to_detach = array_diff($old_collection_ids, $new_collection_ids);
        if ($ids_to_detach) {
            $links_to_collections_dao->detach($link->id, $ids_to_detach);
        }

        return Response::redirect('link', ['id' => $link->id]);
    }

    /**
     * Add a link to the bookmarks
     *
     * @request_param string csrf
     * @request_param string from (default is /bookmarks)
     * @request_param string id
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the link doesn't exist, or is not associated to the
     *               current user
     * @response 302 :from if CSRF is invalid
     * @response 302 :from on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function bookmark($request)
    {
        $user = utils\CurrentUser::get();
        $from = $request->param('from', \Minz\Url::for('bookmarks'));
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            utils\Flash::set('error', _('The link doesn’t exist.'));
            return Response::found($from);
        }

        $bookmarks = $user->bookmarks();
        $actual_collection_ids = array_column($link->collections(), 'id');
        if (in_array($bookmarks->id, $actual_collection_ids)) {
            utils\Flash::set('error', _('This link is already bookmarked.'));
            return Response::found($from);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $links_to_collections_dao->attach($link->id, [$bookmarks->id]);

        return Response::found($from);
    }

    /**
     * Remove a link from bookmarks
     *
     * @request_param string csrf
     * @request_param string from (default is /bookmarks)
     * @request_param string id
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 302 :from if the link doesn't exist, or is not associated to the
     *                     current user, or not in the bookmarks
     * @response 302 :from if CSRF is invalid
     * @response 302 :from on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function unbookmark($request)
    {
        $user = utils\CurrentUser::get();
        $from = $request->param('from', \Minz\Url::for('bookmarks'));
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            utils\Flash::set('error', _('The link doesn’t exist.'));
            return Response::found($from);
        }

        $bookmarks = $user->bookmarks();
        $actual_collection_ids = array_column($link->collections(), 'id');
        if (!in_array($bookmarks->id, $actual_collection_ids)) {
            utils\Flash::set('error', _('This link is not bookmarked.'));
            return Response::found($from);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $links_to_collections_dao->detach($link->id, [$bookmarks->id]);

        return Response::found($from);
    }
}
