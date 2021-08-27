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
     * @request_param string from (default is /links/:id)
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
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $from = $request->param('from', \Minz\Url::for('link', ['id' => $link_id]));

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('link collections', ['id' => $link_id]),
            ]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $collections = $user->collections();
        models\Collection::sort($collections, $user->locale);

        return Response::ok('links/collections/index.phtml', [
            'link' => $link,
            'collection_ids' => array_column($link->collections(), 'id'),
            'collections' => $collections,
            'from' => $from,
        ]);
    }

    /**
     * Update the link collections list
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string[] collection_ids
     * @request_param string from (default is /links/:id)
     *
     * @response 302 /login?redirect_to=/links/:id/collections
     * @response 404 if the link is not found
     * @response 302 :from if CSRF or collection_ids are invalid
     * @response 302 :from
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $new_collection_ids = $request->paramArray('collection_ids', []);
        $from = $request->param('from', \Minz\Url::for('link', ['id' => $link_id]));
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('link collections', ['id' => $link_id]),
            ]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!models\Collection::daoCall('existForUser', $user->id, $new_collection_ids)) {
            utils\Flash::set('error', _('One of the associated collection doesn’t exist.'));
            return Response::found($from);
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $links_to_collections_dao->set($link->id, $new_collection_ids);

        return Response::found($from);
    }
}
