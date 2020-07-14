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
     * @response 302 /login?redirect_to=/links/:id if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 302 /links/:id/fetch if the link is not fetched yet
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function show($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('link', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        if (!$link->fetched_at) {
            return Response::redirect('show fetch link', [
                'id' => $link->id,
            ]);
        }

        $collections = $link->collections();
        models\Collection::sort($collections, $user->locale);

        return Response::ok('links/show.phtml', [
            'link' => $link,
            'collections' => $collections,
        ]);
    }

    /**
     * Create a link for the current user.
     *
     * @request_param string csrf
     * @request_param string from default is /bookmarks
     * @request_param string url It must be a valid non-empty URL
     * @request_param string[] collection_ids It must contain at least one
     *                                        collection id
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 302 :from if CSRF or the url is invalid, of if one collection id
     *                     doesn't exist or parameter is missing/empty
     * @response 302 /links/:id on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function create($request)
    {
        $user = utils\CurrentUser::get();
        $from = $request->param('from', \Minz\Url::for('bookmarks'));
        $url = $request->param('url');
        $collection_ids = $request->param('collection_ids', []);

        if (!$user) {
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

        $link = models\Link::init($url, $user->id);
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
            'user_id' => $user->id,
        ]);
        if ($existing_db_link) {
            $link = new models\Link($existing_db_link);
        } else {
            $link_dao->save($link);
        }

        $existing_collections = $link->collections();
        $existing_collection_ids = array_column($existing_collections, 'id');
        $collection_ids = array_diff($collection_ids, $existing_collection_ids);
        if ($collection_ids) {
            $links_to_collections_dao->attach($link->id, $collection_ids);
        }

        return Response::redirect('link', [
            'id' => $link->id,
        ]);
    }

    /**
     * Show the update link page.
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/:id/edit if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function edit($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit link', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if ($link) {
            return Response::ok('links/edit.phtml', [
                'link' => $link,
                'title' => $link->title,
            ]);
        } else {
            return Response::notFound('not_found.phtml');
        }
    }

    /**
     * Update a link.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string title
     *
     * @response 302 /login?redirect_to=/links/:id/edit if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 400 if csrf token or title are invalid
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
        $new_title = $request->param('title');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit link', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('links/edit.phtml', [
                'link' => $link,
                'title' => $new_title,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link->title = trim($new_title);
        $errors = $link->validate();
        if ($errors) {
            return Response::badRequest('links/edit.phtml', [
                'link' => $link,
                'title' => $new_title,
                'errors' => $errors,
            ]);
        }

        $link_dao = new models\dao\Link();
        $link_dao->save($link);

        return Response::redirect('link', ['id' => $link->id]);
    }

    /**
     * Show the fetch link page.
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/:id/fetch
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function showFetch($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show fetch link', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if ($link) {
            return Response::ok('links/show_fetch.phtml', [
                'link' => $link,
            ]);
        } else {
            return Response::notFound('not_found.phtml');
        }
    }

    /**
     * Fetch information about a link.
     *
     * @request_param string csrf
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/:id/fetch
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 400 if csrf token is invalid
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function fetch($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show fetch link', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('links/show_fetch.phtml', [
                'link' => $link,
                'error' => _('A security verification failed.'),
            ]);
        }

        $cache_path = \Minz\Configuration::$application['cache_path'];
        $cache = new \SpiderBits\Cache($cache_path);
        $url_hash = \SpiderBits\Cache::hash($link->url);
        $cached_response = $cache->get($url_hash);

        if ($cached_response) {
            $response = \SpiderBits\Response::fromText($cached_response);
        } else {
            $http = new \SpiderBits\Http();
            $http->user_agent = 'flusio/0.0.1 (' . PHP_OS . '; https://github.com/flusio/flusio)';
            $http->timeout = 5;
            $response = $http->get($link->url);

            $cache->save($url_hash, (string)$response);
        }

        if ($response->success) {
            $dom = \SpiderBits\Dom::fromText($response->data);
            $title = \SpiderBits\DomExtractor::title($dom);
            if ($title) {
                $link->title = $title;
            }

            $content = \SpiderBits\DomExtractor::content($dom);
            $words = array_filter(explode(' ', $content));
            $link->reading_time = intval(count($words) / 200);
        } else {
            $link->fetched_error = $response->data;
        }

        $link->fetched_code = $response->status;
        $link->fetched_at = \Minz\Time::now();

        $link_dao = new models\dao\Link();
        $link_dao->save($link);

        return Response::ok('links/show_fetch.phtml', [
            'link' => $link,
        ]);
    }

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
    public function collections($request)
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

        return Response::ok('links/collections.phtml', [
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
    public function updateCollections($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');
        $new_collection_ids = $request->param('collections', []);

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
     * Remove a link from a collection.
     *
     * @request_param string csrf
     * @request_param string from (default is /bookmarks)
     * @request_param string id
     * @request_param string collection_id
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the link or collection (or their relation) don't exist,
     *               or are not associated to the current user
     * @response 302 :from if CSRF is invalid
     * @response 302 :from on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function removeCollection($request)
    {
        $user = utils\CurrentUser::get();
        $from = $request->param('from', \Minz\Url::for('bookmarks'));
        $link_id = $request->param('id');
        $collection_id = $request->param('collection_id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $db_link_to_collection = $links_to_collections_dao->findRelation(
            $user->id,
            $link_id,
            $collection_id
        );
        if (!$db_link_to_collection) {
            utils\Flash::set('error', _('This link-collection relation doesn’t exist.'));
            return Response::found($from);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $links_to_collections_dao->delete($db_link_to_collection['id']);

        return Response::found($from);
    }
}
