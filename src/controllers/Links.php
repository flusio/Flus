<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * Handle the requests related to the links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Links
{
    /**
     * List the links page of the current user (it shows the owned collections
     * in fact).
     *
     * @response 302 /login?redirect_to=/links
     *     if the user is not connected
     * @response 200
     */
    public function index($request)
    {
        $user = auth\CurrentUser::get();

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => \Minz\Url::for('links')]);
        }

        $bookmarks = $user->bookmarks();
        $read_list = $user->readList();

        $groups = models\Group::daoToList('listBy', ['user_id' => $user->id]);
        utils\Sorter::localeSort($groups, 'name');

        $collections = $user->collections(['number_links']);
        utils\Sorter::localeSort($collections, 'name');
        $groups_to_collections = utils\Grouper::groupBy($collections, 'group_id');

        return Response::ok('links/index.phtml', [
            'bookmarks' => $bookmarks,
            'read_list' => $read_list,
            'groups' => $groups,
            'groups_to_collections' => $groups_to_collections,
        ]);
    }

    /**
     * Show a link page.
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/:id
     *     if user is not connected and the link is not public
     * @response 404
     *     if the link doesn't exist or is inaccessible to current user
     * @response 200
     */
    public function show($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $link = models\Link::find($link_id);

        $can_view = auth\LinksAccess::canView($user, $link);
        $can_update = auth\LinksAccess::canUpdate($user, $link);
        if (!$can_view && $user) {
            return Response::notFound('not_found.phtml');
        } elseif (!$can_view) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('link', ['id' => $link_id]),
            ]);
        }

        $is_atom_feed = utils\Belt::endsWith($request->path(), 'feed.atom.xml');
        if ($is_atom_feed) {
            $locale = $link->owner()->locale;
            utils\Locale::setCurrentLocale($locale);
            return Response::ok('links/feed.atom.xml.php', [
                'link' => $link,
                'messages' => $link->messages(),
                'user_agent' => \Minz\Configuration::$application['user_agent'],
            ]);
        } elseif ($can_update) {
            $collections = $link->collections();
            utils\Sorter::localeSort($collections, 'name');

            return Response::ok('links/show.phtml', [
                'link' => $link,
                'collections' => $collections,
                'messages' => $link->messages(),
                'comment' => '',
            ]);
        } else {
            return Response::ok('links/show_public.phtml', [
                'link' => $link,
                'messages' => $link->messages(),
            ]);
        }
    }

    /**
     * Show the page to add a link.
     *
     * @request_param string url The URL to prefill the URL input (default is '')
     * @request_param string collection_id Collection to check (default is bookmarks id)
     * @request_param string from The page to redirect to after creation (default is /links/new)
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 200
     */
    public function new($request)
    {
        $user = auth\CurrentUser::get();
        $default_url = $request->param('url', '');
        $from = $request->param('from', \Minz\Url::for('new link'));

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $bookmarks = $user->bookmarks();
        $collections = $user->collections();
        utils\Sorter::localeSort($collections, 'name');
        $collections = array_merge([$bookmarks], $collections);

        $default_collection_id = $request->param('collection_id');
        if ($default_collection_id) {
            $default_collection_ids = [$default_collection_id];
        } else {
            $default_collection_ids = [$bookmarks->id];
        }

        return Response::ok('links/new.phtml', [
            'url' => $default_url,
            'is_hidden' => false,
            'collection_ids' => $default_collection_ids,
            'collections' => $collections,
            'from' => $from,
        ]);
    }

    /**
     * Create a link for the current user.
     *
     * @request_param string csrf
     * @request_param string url It must be a valid non-empty URL
     * @request_param boolean is_hidden
     * @request_param string[] collection_ids It must contain at least one
     *                                        collection id
     * @request_param string from The page to redirect to after creation (default is /links/new)
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 400 if CSRF or the url is invalid, of if one collection id
     *               doesn't exist or parameter is missing/empty
     * @response 302 :from on success
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $url = $request->param('url', '');
        $is_hidden = $request->paramBoolean('is_hidden', false);
        $collection_ids = $request->paramArray('collection_ids', []);
        $from = $request->param('from', \Minz\Url::for('new link', ['url' => $url]));
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $bookmarks = $user->bookmarks();
        $collections = $user->collections();
        utils\Sorter::localeSort($collections, 'name');
        $collections = array_merge([$bookmarks], $collections);

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest('links/new.phtml', [
                'url' => $url,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link = models\Link::init($url, $user->id, $is_hidden);
        $errors = $link->validate();
        if ($errors) {
            return Response::badRequest('links/new.phtml', [
                'url' => $url,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'from' => $from,
                'errors' => $errors,
            ]);
        }

        if (empty($collection_ids)) {
            return Response::badRequest('links/new.phtml', [
                'url' => $url,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'from' => $from,
                'errors' => [
                    'collection_ids' => _('The link must be associated to a collection.'),
                ],
            ]);
        }

        if (!models\Collection::daoCall('doesUserOwnCollections', $user->id, $collection_ids)) {
            return Response::badRequest('links/new.phtml', [
                'url' => $url,
                'is_hidden' => $is_hidden,
                'collection_ids' => $collection_ids,
                'collections' => $collections,
                'from' => $from,
                'errors' => [
                    'collection_ids' => _('One of the associated collection doesn’t exist.'),
                ],
            ]);
        }

        $existing_link = models\Link::findBy([
            'url' => $link->url,
            'user_id' => $user->id,
        ]);
        if ($existing_link) {
            $link = $existing_link;
        } else {
            $link_fetcher_service = new services\LinkFetcher([
                'timeout' => 10,
                'rate_limit' => false,
            ]);
            $link_fetcher_service->fetch($link);
        }

        models\LinkToCollection::attach([$link->id], $collection_ids);

        return Response::found($from);
    }

    /**
     * Show the update link page.
     *
     * @request_param string id
     * @request_param string from (default is /links/:id)
     *
     * @response 302 /login?redirect_to=/links/:id/edit if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 200
     */
    public function edit($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $from = $request->param('from', \Minz\Url::for('link', ['id' => $link_id]));

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit link', ['id' => $link_id]),
            ]);
        }

        $link = models\Link::find($link_id);
        if (auth\LinksAccess::canUpdate($user, $link)) {
            return Response::ok('links/edit.phtml', [
                'link' => $link,
                'title' => $link->title,
                'is_hidden' => $link->is_hidden,
                'from' => $from,
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
     * @request_param boolean is_hidden
     * @request_param string from (default is /links/:id)
     *
     * @response 302 /login?redirect_to=/links/:id/edit if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 302 :from if csrf token or title are invalid
     * @response 302 :from
     */
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $new_title = $request->param('title');
        $is_hidden = $request->paramBoolean('is_hidden', false);
        $from = $request->param('from', \Minz\Url::for('link', ['id' => $link_id]));
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit link', ['id' => $link_id]),
            ]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $link->title = trim($new_title);
        $link->is_hidden = filter_var($is_hidden, FILTER_VALIDATE_BOOLEAN);
        $errors = $link->validate();
        if ($errors) {
            utils\Flash::set('errors', $errors);
            return Response::found($from);
        }

        $link->save();

        return Response::found($from);
    }

    /**
     * Delete a link
     *
     * @request_param string id
     * @request_param string from default is /links/:id
     * @request_param string redirect_to default is /
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the link doesn’t exist or user hasn't access
     * @response 302 :from if csrf is invalid
     * @response 302 :redirect_to on success
     */
    public function delete($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $from = $request->param('from', \Minz\Url::for('link', ['id' => $link_id]));
        $redirect_to = $request->param('redirect_to', \Minz\Url::for('home'));
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canDelete($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        models\Link::delete($link->id);

        return Response::found($redirect_to);
    }

    /**
     * Do nothing, it handles webextension requests on the removed fetch endpoint.
     *
     * @response 200
     */
    public function fetch()
    {
        return \Minz\Response::ok();
    }
}
