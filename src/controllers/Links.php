<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\forms;
use App\models;
use App\search_engine;
use App\services;
use App\utils;

/**
 * Handle the requests related to the links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Links extends BaseController
{
    /**
     * Display the links page of the current user (it shows the owned
     * collections in fact).
     *
     * @request_param string q
     * @request_param integer page
     *
     * @response 302 /login?redirect_to=/links
     *     if the user is not connected
     * @response 200
     */
    public function index(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('links'));

        $query = $request->parameters->getString('q');
        $pagination_page = $request->parameters->getInteger('page', 1);

        if ($query) {
            $search_query = search_engine\Query::fromString($query);

            $number_links = search_engine\LinksSearcher::countLinks($user, $search_query);

            $number_per_page = 30;

            $pagination = new utils\Pagination($number_links, $number_per_page, $pagination_page);

            if ($pagination_page !== $pagination->currentPage()) {
                return Response::redirect('links', [
                    'q' => $query,
                    'page' => $pagination->currentPage(),
                ]);
            }

            $links = search_engine\LinksSearcher::getLinks(
                $user,
                $search_query,
                pagination: [
                    'offset' => $pagination->currentOffset(),
                    'limit' => $pagination->numberPerPage(),
                ]
            );

            return Response::ok('links/search.phtml', [
                'links' => $links,
                'query' => $query,
                'pagination' => $pagination,
            ]);
        } else {
            $bookmarks = $user->bookmarks();
            $read_list = $user->readList();

            $groups = models\Group::listBy(['user_id' => $user->id]);
            $groups = utils\Sorter::localeSort($groups, 'name');

            $collections = $user->collections(['number_links']);
            $collections = utils\Sorter::localeSort($collections, 'name');
            $groups_to_collections = utils\Grouper::groupBy($collections, 'group_id');

            $shared_collections = $user->sharedCollections(['number_links'], [
                'access_type' => 'write',
            ]);
            $shared_collections = utils\Sorter::localeSort($shared_collections, 'name');

            return Response::ok('links/index.phtml', [
                'bookmarks' => $bookmarks,
                'read_list' => $read_list,
                'groups' => $groups,
                'groups_to_collections' => $groups_to_collections,
                'shared_collections' => $shared_collections,
                'query' => $query,
            ]);
        }
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
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->parameters->getString('id', '');
        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        $can_view = auth\LinksAccess::canView($user, $link);
        $can_update = auth\LinksAccess::canUpdate($user, $link);
        if (!$can_view && $user) {
            return Response::notFound('not_found.phtml');
        } elseif (!$can_view) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('link', ['id' => $link_id]),
            ]);
        }

        if ($user) {
            $mastodon_configured = models\MastodonAccount::existsBy([
                'user_id' => $user->id,
            ]);
        } else {
            $mastodon_configured = false;
        }

        return Response::ok('links/show.phtml', [
            'link' => $link,
            'can_update' => $can_update,
            'content' => '',
            'share_on_mastodon' => false,
            'mastodon_configured' => $mastodon_configured,
        ]);
    }

    /**
     * Show the page to add a link.
     *
     * @request_param string url The URL to prefill the URL input (default is '')
     * @request_param string collection_id Collection to check (default is bookmarks id)
     *
     * @response 302 /login?redirect_to=/links/new if not connected
     * @response 200
     */
    public function new(Request $request): Response
    {
        $default_url = $request->parameters->getString('url', '');
        $default_collection_id = $request->parameters->getString('collection_id');

        $from = \Minz\Url::for('new link', [
            'url' => $default_url,
            'collection_id' => $default_collection_id,
        ]);
        $user = $this->requireCurrentUser(redirect_after_login: $from);

        if ($default_collection_id) {
            $default_collection_ids = [$default_collection_id];
        } else {
            $bookmarks = $user->bookmarks();
            $default_collection_ids = [$bookmarks->id];
        }

        $link = new models\Link($default_url, $user->id);
        $form = new forms\NewLink([
            'collection_ids' => $default_collection_ids,
        ], $link);

        return Response::ok('links/new.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * Create a link for the current user.
     *
     * @request_param string url
     * @request_param string[] collection_ids
     * @request_param string[] new_collection_names
     * @request_param boolean is_hidden
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=/links/new
     *     If not connected.
     * @response 400
     *     If CSRF or the url is invalid, if one collection id doesn't exist
     *     or if both collection_ids and new_collection_names parameters are
     *     missing/empty.
     * @response 302 /links/:id
     *     On success.
     */
    public function create(Request $request): Response
    {
        $url = $request->parameters->getString('url', '');

        $from = \Minz\Url::for('new link', ['url' => $url]);
        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = $user->findOrBuildLink($url);
        $form = new forms\NewLink(model: $link);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/new.phtml', [
                'form' => $form,
            ]);
        }

        $link = $form->model();

        if (!$link->isPersisted()) {
            $link_fetcher_service = new services\LinkFetcher([
                'http_timeout' => 10,
                'ignore_rate_limit' => true,
            ]);
            $link_fetcher_service->fetch($link);
        }

        $link_collections = $form->selectedCollections();

        foreach ($form->newCollections() as $collection) {
            $collection->save();

            $link_collections[] = $collection;
        }

        $link->addCollections($link_collections);

        return Response::redirect('link', [
            'id' => $link->id,
        ]);
    }

    /**
     * Show the update link page.
     *
     * @request_param string id
     * @request_param string from (default is /links/:id)
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 200
     */
    public function edit(Request $request): Response
    {
        $link_id = $request->parameters->getString('id', '');
        $from = $request->parameters->getString('from', \Minz\Url::for('link', ['id' => $link_id]));

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = models\Link::find($link_id);
        if ($link && auth\LinksAccess::canUpdate($user, $link)) {
            return Response::ok('links/edit.phtml', [
                'link' => $link,
                'title' => $link->title,
                'reading_time' => $link->reading_time,
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
     * @request_param integer reading_time
     * @request_param string from (default is /links/:id)
     *
     * @response 302 /login?redirect_to=/links/:id if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 400 :from if csrf token or title are invalid
     * @response 302 :from
     */
    public function update(Request $request): Response
    {
        $link_id = $request->parameters->getString('id', '');
        $new_title = $request->parameters->getString('title', '');
        $new_reading_time = $request->parameters->getInteger('reading_time', 0);
        $from = $request->parameters->getString('from', \Minz\Url::for('link', ['id' => $link_id]));
        $csrf = $request->parameters->getString('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('links/edit.phtml', [
                'link' => $link,
                'title' => $new_title,
                'reading_time' => $new_reading_time,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $old_title = $link->title;
        $old_reading_time = $link->reading_time;

        $link->title = trim($new_title);
        $link->reading_time = $new_reading_time;
        if (!$link->validate()) {
            $link->title = $old_title;
            $link->reading_time = $old_reading_time;
            return Response::badRequest('links/edit.phtml', [
                'link' => $link,
                'title' => $new_title,
                'reading_time' => $new_reading_time,
                'from' => $from,
                'errors' => $link->errors(),
            ]);
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
    public function delete(Request $request): Response
    {
        $link_id = $request->parameters->getString('id', '');
        $from = $request->parameters->getString('from', \Minz\Url::for('link', ['id' => $link_id]));
        $redirect_to = $request->parameters->getString('redirect_to', \Minz\Url::for('home'));
        $csrf = $request->parameters->getString('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canDelete($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        models\Link::delete($link->id);

        return Response::found($redirect_to);
    }
}
