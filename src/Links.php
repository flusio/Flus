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
     * @response 302 /login?redirect_to=/links/:id
     *     if user is not connected and the link is not public
     * @response 404
     *     if the link doesn't exist or is inaccessible to current user
     * @response 302 /links/:id/fetch
     *     if the link is owned by the current user and is not fetched yet
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
        $link_dao = new models\dao\Link();
        $db_link = $link_dao->find($link_id);

        // We must show the link in 3 main cases:
        // 1. the visitor is connected and owns the link
        // 2. the visitor is connected and the link is public
        // 3. the visitor is not connected and the link is public

        // The first thing to do is to calculate the 3 boolean variables which
        // will allow us to handle these different cases.
        $is_connected = $user !== null;
        $is_owned = $is_connected && $db_link && $user->id === $db_link['user_id'];
        $is_public = $db_link && $db_link['is_public'];

        // This branch handles case 1
        if ($is_connected && $is_owned) {
            $link = new models\Link($db_link);
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
                'messages' => $link->messages(),
                'comment' => '',
            ]);
        }

        // This branch handles cases 2 and 3
        if ($is_public) {
            $link = new models\Link($db_link);
            return Response::ok('links/show_public.phtml', [
                'link' => $link,
                'messages' => $link->messages(),
            ]);
        }

        // At this point, we know we don't want to give (direct) access to the
        // link. The response still depends on wether the user is connected or
        // not.
        if ($is_connected) {
            return Response::notFound('not_found.phtml');
        } else {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('link', ['id' => $link_id]),
            ]);
        }
    }

    /**
     * Show the page to add a link.
     *
     * @request_param string url The URL to prefill the URL input (default is '')
     * @request_param string[] collection_ids Collection to check (default contains bookmarks id)
     *
     * @response 302 /login?redirect_to=/links/new if not connected
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function new($request)
    {
        $user = utils\CurrentUser::get();
        $default_url = $request->param('url', '');

        if (!$user) {
            if ($default_url) {
                $redirect_to = \Minz\Url::for('new link', ['url' => $default_url]);
            } else {
                $redirect_to = \Minz\Url::for('new link');
            }

            return Response::redirect('login', ['redirect_to' => $redirect_to]);
        }

        $collections = $user->collections();
        models\Collection::sort($collections, $user->locale);

        $default_collection_id = $request->param('collection');
        if ($default_collection_id) {
            $default_collection_ids = [$default_collection_id];
        } else {
            $bookmarks_collection = $user->bookmarks();
            $default_collection_ids = [$bookmarks_collection->id];
        }

        return Response::ok('links/new.phtml', [
            'url' => $default_url,
            'is_public' => false,
            'collection_ids' => $default_collection_ids,
            'collections' => $collections,
        ]);
    }

    /**
     * Create a link for the current user.
     *
     * @request_param string csrf
     * @request_param string url It must be a valid non-empty URL
     * @request_param boolean is_public
     * @request_param string[] collection_ids It must contain at least one
     *                                        collection id
     *
     * @response 302 /login?redirect_to=/links/new if not connected
     * @response 400 if CSRF or the url is invalid, of if one collection id
     *               doesn't exist or parameter is missing/empty
     * @response 302 /links/:id on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function create($request)
    {
        $user = utils\CurrentUser::get();
        $url = $request->param('url', '');
        $is_public = $request->param('is_public', false);
        $collection_ids = $request->param('collection_ids', []);

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('new link', ['url' => $url]),
            ]);
        }

        $collections = $user->collections();
        models\Collection::sort($collections, $user->locale);

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('links/new.phtml', [
                'url' => $url,
                'is_public' => $is_public,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link_dao = new models\dao\Link();
        $collection_dao = new models\dao\Collection();
        $links_to_collections_dao = new models\dao\LinksToCollections();

        $link = models\Link::init($url, $user->id, $is_public);
        $errors = $link->validate();
        if ($errors) {
            return Response::badRequest('links/new.phtml', [
                'url' => $url,
                'is_public' => $is_public,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'errors' => $errors,
            ]);
        }

        if (empty($collection_ids)) {
            return Response::badRequest('links/new.phtml', [
                'url' => $url,
                'is_public' => $is_public,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'errors' => [
                    'collection_ids' => _('The link must be associated to a collection.'),
                ],
            ]);
        }

        if (!$collection_dao->exists($collection_ids)) {
            return Response::badRequest('links/new.phtml', [
                'url' => $url,
                'is_public' => $is_public,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'errors' => [
                    'collection_ids' => _('One of the associated collection doesn’t exist.'),
                ],
            ]);
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
                'is_public' => $link->is_public,
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
     * @request_param boolean is_public
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
        $is_public = $request->param('is_public', false);

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
                'is_public' => $is_public,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link->title = trim($new_title);
        $link->is_public = filter_var($is_public, FILTER_VALIDATE_BOOLEAN);
        $errors = $link->validate();
        if ($errors) {
            return Response::badRequest('links/edit.phtml', [
                'link' => $link,
                'title' => $new_title,
                'is_public' => $is_public,
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
            $php_os = PHP_OS;
            $flusio_version = \Minz\Configuration::$application['version'];
            $http->user_agent = "flusio/{$flusio_version} ({$php_os}; https://github.com/flusio/flusio)";
            $http->timeout = 5;
            $response = $http->get($link->url);

            $cache->save($url_hash, (string)$response);
        }

        if ($response->success) {
            $content_type = $response->header('content-type');
            if (utils\Belt::contains($content_type, 'text/html')) {
                $dom = \SpiderBits\Dom::fromText($response->data);
                $title = \SpiderBits\DomExtractor::title($dom);
                if ($title) {
                    $link->title = $title;
                }

                $content = \SpiderBits\DomExtractor::content($dom);
                $words = array_filter(explode(' ', $content));
                $link->reading_time = intval(count($words) / 200);
            }
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
     * Show the page to share a link.
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/:id/share
     *     if not connected
     * @response 404
     *     if the link doesn't exist or not associated to the current user
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function sharing($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('sharing link', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('links/sharing.phtml', [
            'link' => $link,
        ]);
    }

    /**
     * Allow to change the visibility of a link
     *
     * @request_param string id
     * @request_param string visibility (`public` or `private`)
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/links/:id/share
     *     if not connected
     * @response 404
     *     if the link doesn't exist or not associated to the current user
     * @response 302 /links/:id/share
     *     if the CSRF token is invalid
     * @response 302 /links/:id/share
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function updateVisibility($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('id');
        $visibility = $request->param('visibility', 'private');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('sharing link', ['id' => $link_id]),
            ]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::redirect('sharing link', ['id' => $link->id]);
        }

        if ($visibility === 'public') {
            $link->is_public = true;
        } else {
            $link->is_public = false;
        }

        $link_dao = new models\dao\Link();
        $link_dao->save($link);

        return Response::redirect('sharing link', ['id' => $link->id]);
    }

    /**
     * Remove a link from news and bookmarks.
     *
     * @request_param string csrf
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 /news
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function read($request)
    {
        $user = utils\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            utils\Flash::set('error', _('The link doesn’t exist.'));
            return Response::found($from);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $bookmarks = $user->bookmarks();

        $links_to_collections_dao = new models\dao\LinksToCollections();
        $link_dao = new models\dao\Link();

        $link->in_news = false;
        $link_dao->save($link);
        $links_to_collections_dao->detach($link->id, [$bookmarks->id]);

        return Response::found($from);
    }

    /**
     * Remove a link from news only.
     *
     * @request_param string csrf
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/news
     *     if not connected
     * @response 302 /news
     *     if the link doesn't exist, or is not associated to the current user
     * @response 302 /news
     *     if CSRF is invalid
     * @response 302 /news
     *     on success
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function readLater($request)
    {
        $user = utils\CurrentUser::get();
        $from = \Minz\Url::for('news');
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            utils\Flash::set('error', _('The link doesn’t exist.'));
            return Response::found($from);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $link_dao = new models\dao\Link();

        $link->in_news = false;
        $link_dao->save($link);

        return Response::found($from);
    }
}
