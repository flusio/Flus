<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests related to the links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Links
{
    /**
     * Show a link page.
     *
     * @request_param string id
     *
     * @response 200
     * @response 401 if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function show($request)
    {
        $current_user = utils\CurrentUser::get();
        if (!$current_user) {
            return Response::unauthorized('unauthorized.phtml');
        }

        $link_dao = new models\dao\Link();
        $db_link = $link_dao->findBy([
            'id' => $request->param('id'),
            'user_id' => $current_user->id,
        ]);

        if (!$db_link) {
            return Response::notFound('not_found.phtml', [
                'error' => _('This link doesn’t exist.'),
            ]);
        }

        $link = new models\Link($db_link);
        return Response::ok('links/show.phtml', [
            'link' => $link,
        ]);
    }

    /**
     * Add a link for the current user.
     *
     * @request_param string csrf
     * @request_param string from
     * @request_param string url It must be a valid non-empty URL
     * @request_param string[] collection_ids It must contain at least one
     *                                        collection id
     *
     * @response 302 /links/:id on success
     * @response 302 [from] if CSRF or the url is invalid, of if one collection id
     *                      doesn't exist or parameter is missing/empty
     * @response 302 /login?redirect_to=[from] if not connected
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function add($request)
    {
        $current_user = utils\CurrentUser::get();
        $from = $request->param('from', \Minz\Url::for('show bookmarked'));

        if (!$current_user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set(
                'error',
                _('A security verification failed: you should retry to submit the form.')
            );
            return Response::found($from);
        }

        $link_dao = new models\dao\Link();
        $collection_dao = new models\dao\Collection();
        $links_to_collections_dao = new models\dao\LinksToCollections();
        $url = $request->param('url');
        $collection_ids = $request->param('collection_ids', []);

        $link = models\Link::init($url, $current_user->id);

        $errors = $link->validate();
        if ($errors) {
            utils\Flash::set('errors', ['url' => $errors['url']]);
            return Response::found($from);
        }

        if (empty($collection_ids)) {
            utils\Flash::set('error', _('The link must be associated to a collection.'));
            return Response::found($from);
        }

        if (!$collection_dao->exists($collection_ids)) {
            utils\Flash::set('error', _('One of the associated collection doesn’t exist.'));
            return Response::found($from);
        }

        $existing_db_link = $link_dao->findBy([
            'url' => $link->url,
            'user_id' => $current_user->id,
        ]);
        if ($existing_db_link) {
            $link = new models\Link($existing_db_link);
        } else {
            $link_dao->save($link);
        }

        $existing_collection_ids = $link->collectionIds();
        $collection_ids = array_diff($collection_ids, $existing_collection_ids);
        if ($collection_ids) {
            $links_to_collections_dao->attachCollectionsToLink($link->id, $collection_ids);
        }

        return Response::redirect('show link', [
            'id' => $link->id,
        ]);
    }
}
