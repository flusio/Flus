<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * Handle the requests related to the collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections extends BaseController
{
    /**
     * Show the page listing all the collections of the current user
     *
     * This page is deprecated an no longer used. It has been replaced by the
     * "My links" and "Feeds" pages.
     *
     * @response 301 /links
     */
    public function index(): Response
    {
        $url = \Minz\Url::for('links');
        return Response::movedPermanently($url);
    }

    /**
     * Show the page to create a collection.
     *
     * @response 200
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function new(): Response
    {
        auth\CurrentUser::require();

        $form = new forms\collections\Collection();

        return Response::ok('collections/new.phtml', [
            'form' => $form,
        ]);
    }

    /**
     * Create a collection.
     *
     * @request_param string name
     * @request_param string description
     * @request_param string[] topic_ids
     * @request_param boolean is_public
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /collections/:id
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $collection = $user->initCollection();
        $form = new forms\collections\Collection(model: $collection);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('collections/new.phtml', [
                'form' => $form,
            ]);
        }

        $collection = $form->model();

        $collection->save();

        $collection->setTopics($form->selectedTopics());

        return Response::redirect('collection', ['id' => $collection->id]);
    }

    /**
     * Show a collection page.
     *
     * @request_param string id
     * @request_param integer page
     *
     * @response 404
     *     If the requested page is out of bound.
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the collection requires the users to be logged in while they are not.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the collection.
     * @throws utils\PaginationOutOfBoundsError
     *     If the requested page is out of the pagination bounds.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $collection = models\Collection::requireFromRequest($request);

        if ($user) {
            auth\Access::require($user, 'view', $collection);
        } elseif (!auth\Access::can($user, 'view', $collection)) {
            auth\CurrentUser::require();
        }

        $can_update = auth\Access::can($user, 'update', $collection);

        $access_is_shared = $user && $collection->sharedWith($user);
        $number_links = models\Link::countByCollectionId($collection->id, [
            'hidden' => $can_update || $access_is_shared,
        ]);
        $number_per_page = $can_update ? 29 : 30; // the button to add a link counts for 1!
        $pagination_page = $request->parameters->getInteger('page', 1);
        $pagination = new utils\Pagination($number_links, $number_per_page, $pagination_page);

        $topics = $collection->topics();
        $topics = utils\Sorter::localeSort($topics, 'label');

        if ($can_update) {
            return Response::ok('collections/show.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'links' => $collection->links(
                    ['published_at', 'number_notes'],
                    [
                        'offset' => $pagination->currentOffset(),
                        'limit' => $pagination->numberPerPage(),
                    ]
                ),
                'pagination' => $pagination,
            ]);
        } else {
            return Response::ok('collections/show_public.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'links' => $collection->links(
                    ['published_at', 'number_notes'],
                    [
                        'hidden' => $access_is_shared,
                        'offset' => $pagination->currentOffset(),
                        'limit' => $pagination->numberPerPage(),
                    ]
                ),
                'pagination' => $pagination,
            ]);
        }
    }

    /**
     * Show the edition page of a collection.
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
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'update', $collection);

        $form = new forms\collections\Collection([
            'topic_ids' => array_column($collection->topics(), 'id'),
        ], model: $collection);

        return Response::ok('collections/edit.phtml', [
            'collection' => $collection,
            'form' => $form,
        ]);
    }

    /**
     * Update a collection.
     *
     * @request_param string id
     * @request_param string name
     * @request_param string description
     * @request_param string[] topic_ids
     * @request_param boolean is_public
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
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'update', $collection);

        $form = new forms\collections\Collection(model: $collection);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('collections/edit.phtml', [
                'collection' => $collection,
                'form' => $form,
            ]);
        }

        $collection = $form->model();

        $collection->save();

        $collection->setTopics($form->selectedTopics());

        return Response::found(utils\RequestHelper::from($request));
    }

    /**
     * Delete a collection.
     *
     * @request_param string id
     * @request_param string csrf_token
     *
     * @response 302 :from
     *     If the CSRF token is invalid.
     * @response 302 /links
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot delete the collection.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'delete', $collection);

        $from = utils\RequestHelper::from($request);

        $form = new forms\collections\DeleteCollection();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $collection->remove();

        return Response::redirect('links');
    }
}
