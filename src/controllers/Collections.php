<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\utils;

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
     * Show the page to create a collection
     *
     * @response 302 /login?redirect_to=/collections/new if not connected
     * @response 200
     */
    public function new(): Response
    {
        $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('new collection'));

        $topics = models\Topic::listAll();
        $topics = utils\Sorter::localeSort($topics, 'label');

        return Response::ok('collections/new.phtml', [
            'name' => '',
            'description' => '',
            'is_public' => false,
            'topics' => $topics,
            'topic_ids' => [],
            'name_max_length' => models\Collection::NAME_MAX_LENGTH,
        ]);
    }

    /**
     * Create a collection
     *
     * @request_param string csrf
     * @request_param string name
     * @request_param string description
     * @request_param string[] topic_ids
     * @request_param boolean is_public
     *
     * @response 302 /login?redirect_to=/collections/new if not connected
     * @response 400 if csrf, name or topic_ids are invalid
     * @response 302 /collections/:new
     */
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('new collection'));

        $name = $request->param('name', '');
        $description = $request->param('description', '');
        /** @var string[] */
        $topic_ids = $request->paramArray('topic_ids', []);
        $is_public = $request->paramBoolean('is_public', false);
        $csrf = $request->param('csrf', '');

        $topics = models\Topic::listAll();
        $topics = utils\Sorter::localeSort($topics, 'label');

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('collections/new.phtml', [
                'name' => $name,
                'description' => $description,
                'topic_ids' => $topic_ids,
                'is_public' => $is_public,
                'topics' => $topics,
                'name_max_length' => models\Collection::NAME_MAX_LENGTH,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($topic_ids && !models\Topic::existsBy(['id' => $topic_ids])) {
            return Response::badRequest('collections/new.phtml', [
                'name' => $name,
                'description' => $description,
                'topic_ids' => $topic_ids,
                'is_public' => $is_public,
                'topics' => $topics,
                'name_max_length' => models\Collection::NAME_MAX_LENGTH,
                'errors' => [
                    'topic_ids' => _('One of the associated topic doesn’t exist.'),
                ],
            ]);
        }

        $collection = models\Collection::init($user->id, $name, $description, $is_public);
        $errors = $collection->validate();
        if ($errors) {
            return Response::badRequest('collections/new.phtml', [
                'name' => $name,
                'description' => $description,
                'topic_ids' => $topic_ids,
                'is_public' => $is_public,
                'topics' => $topics,
                'name_max_length' => models\Collection::NAME_MAX_LENGTH,
                'errors' => $errors,
            ]);
        }

        $collection->save();
        if ($topic_ids) {
            models\CollectionToTopic::attach($collection->id, $topic_ids);
        }

        return Response::redirect('collection', ['id' => $collection->id]);
    }

    /**
     * Show a collection page
     *
     * @request_param string id
     * @request_param integer page
     *
     * @response 302 /login?redirect_to=/collection/:id
     *     if user is not connected and the collection is not public
     * @response 404
     *     if the collection doesn’t exist or is inaccessible to the current user
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id', '');
        $pagination_page = $request->paramInteger('page', 1);
        $collection = models\Collection::find($collection_id);

        if (!$collection) {
            return Response::notFound('not_found.phtml');
        }

        $can_view = auth\CollectionsAccess::canView($user, $collection);
        $can_update = auth\CollectionsAccess::canUpdate($user, $collection);
        if (!$can_view && $user) {
            return Response::notFound('not_found.phtml');
        } elseif (!$can_view) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('collection', ['id' => $collection_id]),
            ]);
        }

        $access_is_shared = $user && $collection->sharedWith($user);
        $number_links = models\Link::countByCollectionId($collection->id, [
            'hidden' => $can_update || $access_is_shared,
        ]);
        $number_per_page = $can_update ? 29 : 30; // the button to add a link counts for 1!
        $pagination = new utils\Pagination($number_links, $number_per_page, $pagination_page);
        if ($pagination_page !== $pagination->currentPage()) {
            return Response::redirect('collection', [
                'id' => $collection->id,
                'page' => $pagination->currentPage(),
            ]);
        }

        $topics = $collection->topics();
        $topics = utils\Sorter::localeSort($topics, 'label');

        if ($user && $can_update) {
            return Response::ok('collections/show.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'links' => $collection->links(
                    ['published_at', 'number_comments'],
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
                    ['published_at', 'number_comments'],
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
     * Show the edition page of a collection
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 200
     */
    public function edit(Request $request): Response
    {
        $collection_id = $request->param('id', '');
        $from = $request->param('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        if ($collection && auth\CollectionsAccess::canUpdate($user, $collection)) {
            $topics = models\Topic::listAll();
            $topics = utils\Sorter::localeSort($topics, 'label');

            return Response::ok('collections/edit.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'name' => $collection->name,
                'description' => $collection->description,
                'is_public' => $collection->is_public,
                'topic_ids' => array_column($collection->topics(), 'id'),
                'from' => $from,
                'name_max_length' => models\Collection::NAME_MAX_LENGTH,
            ]);
        } else {
            return Response::notFound('not_found.phtml');
        }
    }

    /**
     * Update a collection
     *
     * @request_param string id
     * @request_param string csrf
     * @request_param string name
     * @request_param string description
     * @request_param string[] topic_ids
     * @request_param boolean is_public
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 400 if csrf, name or topic_ids are invalid
     * @response 302 :from
     */
    public function update(Request $request): Response
    {
        $collection_id = $request->param('id', '');
        $from = $request->param('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        if (!$collection || !auth\CollectionsAccess::canUpdate($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        $topics = models\Topic::listAll();
        $topics = utils\Sorter::localeSort($topics, 'label');

        $name = $request->param('name', '');
        $description = $request->param('description', '');
        $is_public = $request->paramBoolean('is_public', false);
        /** @var string[] */
        $topic_ids = $request->paramArray('topic_ids', []);
        $csrf = $request->param('csrf', '');

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('collections/edit.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'name' => $name,
                'description' => $description,
                'is_public' => $is_public,
                'topic_ids' => $topic_ids,
                'from' => $from,
                'name_max_length' => models\Collection::NAME_MAX_LENGTH,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($topic_ids && !models\Topic::existsBy(['id' => $topic_ids])) {
            return Response::badRequest('collections/edit.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'name' => $name,
                'description' => $description,
                'is_public' => $is_public,
                'topic_ids' => $topic_ids,
                'from' => $from,
                'name_max_length' => models\Collection::NAME_MAX_LENGTH,
                'errors' => [
                    'topic_ids' => _('One of the associated topic doesn’t exist.'),
                ],
            ]);
        }

        $collection->name = trim($name);
        $collection->description = trim($description);
        $collection->is_public = filter_var($is_public, FILTER_VALIDATE_BOOLEAN);
        $errors = $collection->validate();
        if ($errors) {
            return Response::badRequest('collections/edit.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'name' => $name,
                'description' => $description,
                'is_public' => $is_public,
                'topic_ids' => $topic_ids,
                'from' => $from,
                'name_max_length' => models\Collection::NAME_MAX_LENGTH,
                'errors' => $errors,
            ]);
        }

        $collection->save();

        models\CollectionToTopic::set($collection->id, $topic_ids);

        return Response::found($from);
    }

    /**
     * Delete a collection
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 302 :from if csrf is invalid
     * @response 302 /collections
     */
    public function delete(Request $request): Response
    {
        $collection_id = $request->param('id', '');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        if (!$collection || !auth\CollectionsAccess::canDelete($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        models\Collection::delete($collection->id);

        return Response::redirect('links');
    }
}
