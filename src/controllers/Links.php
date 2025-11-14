<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\search_engine;
use App\services;
use App\utils;
use Minz\Request;
use Minz\Response;

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
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws utils\PaginationOutOfBoundsError
     *     If the requested page is out of the pagination bounds.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $query = $request->parameters->getString('q');
        $pagination_page = $request->parameters->getInteger('page', 1);

        if ($query) {
            $search_query = search_engine\Query::fromString($query);

            $number_links = search_engine\LinksSearcher::countLinks($user, $search_query);

            $number_per_page = 30;

            $pagination = new utils\Pagination($number_links, $number_per_page, $pagination_page);

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
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the link requires the users to be logged in while they are not.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the authenticated user cannot view the link.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $link = models\Link::requireFromRequest($request);

        if ($user) {
            auth\Access::require($user, 'view', $link);
        } elseif (!auth\Access::can($user, 'view', $link)) {
            auth\CurrentUser::require();
        }

        $form = null;
        if ($user && auth\Access::can($user, 'update', $link)) {
            $form = new forms\notes\NewNote(options: [
                'enable_mastodon' => $user->isMastodonEnabled(),
            ]);
        }

        return Response::ok('links/show.phtml', [
            'link' => $link,
            'form' => $form,
        ]);
    }

    /**
     * Show the page to add a link.
     *
     * @request_param string url
     *     An optional URL to prefill the URL input.
     * @request_param string collection_id
     *     An optional collection to select by default.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $default_url = $request->parameters->getString('url', '');
        $default_collection_id = $request->parameters->getString('collection_id');

        $default_collection_ids = [];
        if ($default_collection_id) {
            $default_collection_ids[] = $default_collection_id;
        }

        $link = new models\Link($default_url, $user->id);
        $form = new forms\links\NewLink([
            'collection_ids' => $default_collection_ids,
        ], $link, [
            'user' => $user,
        ]);

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
     * @request_param boolean read_later
     * @request_param boolean is_hidden
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /links/:id
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $url = $request->parameters->getString('url', '');

        $link = $user->findOrBuildLink($url);
        $form = new forms\links\NewLink(model: $link, options: [
            'user' => $user,
        ]);

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

        if ($form->read_later) {
            $user->markAsReadLater($link);
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
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the link.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'update', $link);

        $form = new forms\links\EditLink(model: $link);

        return Response::ok('links/edit.phtml', [
            'link' => $link,
            'form' => $form,
        ]);
    }

    /**
     * Update a link.
     *
     * @request_param string id
     * @request_param string title
     * @request_param integer reading_time
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the link.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'update', $link);

        $form = new forms\links\EditLink(model: $link);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/edit.phtml', [
                'link' => $link,
                'form' => $form,
            ]);
        }

        $link = $form->model();
        $link->save();

        return Response::found(utils\RequestHelper::from($request));
    }

    /**
     * Delete a link.
     *
     * @request_param string id
     * @request_param string csrf_token
     *
     * @response 302 :from
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot delete the link.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'delete', $link);

        $from = utils\RequestHelper::from($request);

        $form = new forms\links\DeleteLink();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $link->remove();

        return Response::found($from);
    }
}
