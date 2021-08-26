<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * Handle the requests related to the collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections
{
    /**
     * Show the page listing all the collections of the current user
     *
     * @response 302 /login?redirect_to=/collections if not connected
     * @response 200
     */
    public function index()
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('collections'),
            ]);
        }

        $no_group_collections = models\Collection::daoToList('listInGroup', $user->id, null);
        $no_group_followed_collections = models\Collection::daoToList('listFollowedInGroup', $user->id, null);
        models\Collection::sort($no_group_collections, $user->locale);
        models\Collection::sort($no_group_followed_collections, $user->locale);

        $groups = models\Group::daoToList('listBy', ['user_id' => $user->id]);
        models\Group::sort($groups, $user->locale);

        return Response::ok('collections/index.phtml', [
            'no_group_collections' => $no_group_collections,
            'no_group_followed_collections' => $no_group_followed_collections,
            'groups' => $groups,
        ]);
    }

    /**
     * Show the page to create a collection
     *
     * @response 302 /login?redirect_to=/collections/new if not connected
     * @response 200
     */
    public function new()
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('new collection'),
            ]);
        }

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

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
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('new collection'),
            ]);
        }

        $name = $request->param('name', '');
        $description = $request->param('description', '');
        $topic_ids = $request->paramArray('topic_ids', []);
        $is_public = $request->paramBoolean('is_public', false);
        $csrf = $request->param('csrf');

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        if (!\Minz\CSRF::validate($csrf)) {
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

        if ($topic_ids && !models\Topic::exists($topic_ids)) {
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
            $collections_to_topics_dao = new models\dao\CollectionsToTopics();
            $collections_to_topics_dao->attach($collection->id, $topic_ids);
        }

        return Response::redirect('collection', ['id' => $collection->id]);
    }

    /**
     * Show a collection page
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/collection/:id
     *     if user is not connected and the collection is not public
     * @response 404
     *     if the collection doesn’t exist or is inaccessible to the current user
     * @response 200
     */
    public function show($request)
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id');
        $collection = models\Collection::find($collection_id);

        $can_view = auth\CollectionsAccess::canView($user, $collection);
        $can_update = auth\CollectionsAccess::canUpdate($user, $collection);
        if (!$can_view && $user) {
            return Response::notFound('not_found.phtml');
        } elseif (!$can_view) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('collection', ['id' => $collection_id]),
            ]);
        }

        $number_links = models\Link::daoCall('countByCollectionId', $collection->id, !$can_update);
        $pagination_page = intval($request->param('page', 1));
        $number_per_page = $can_update ? 29 : 30; // the button to add a link counts for 1!
        $pagination = new utils\Pagination($number_links, $number_per_page, $pagination_page);
        if ($pagination_page !== $pagination->currentPage()) {
            return Response::redirect('collection', [
                'id' => $collection->id,
                'page' => $pagination->currentPage(),
            ]);
        }

        $topics = $collection->topics();
        models\Topic::sort($topics, utils\Locale::currentLocale());

        $is_atom_feed = utils\Belt::endsWith($request->path(), 'feed.atom.xml');
        if ($is_atom_feed) {
            $locale = $collection->owner()->locale;
            utils\Locale::setCurrentLocale($locale);
            $response = Response::ok('collections/feed.atom.xml.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'links' => $collection->visibleLinks(),
                'user_agent' => \Minz\Configuration::$application['user_agent'],
            ]);
            $response->setHeader('Content-Type', 'application/atom+xml;charset=UTF-8');
            return $response;
        } elseif ($can_update) {
            return Response::ok('collections/show.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'links' => $collection->links(
                    $pagination->currentOffset(),
                    $pagination->numberPerPage()
                ),
                'pagination' => $pagination,
            ]);
        } else {
            return Response::ok('collections/show_public.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'links' => $collection->visibleLinks(
                    $pagination->currentOffset(),
                    $pagination->numberPerPage()
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
    public function edit($request)
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id');
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        if (auth\CollectionsAccess::canUpdate($user, $collection)) {
            $topics = models\Topic::listAll();
            models\Topic::sort($topics, $user->locale);

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
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id');
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        if (!auth\CollectionsAccess::canUpdate($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        $name = $request->param('name', '');
        $description = $request->param('description', '');
        $is_public = $request->paramBoolean('is_public', false);
        $topic_ids = $request->paramArray('topic_ids', []);
        $csrf = $request->param('csrf');

        if (!\Minz\CSRF::validate($csrf)) {
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

        if ($topic_ids && !models\Topic::exists($topic_ids)) {
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
        $collections_to_topics_dao = new models\dao\CollectionsToTopics();
        $collections_to_topics_dao->set($collection->id, $topic_ids);

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
    public function delete($request)
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id');
        $from = $request->param('from');
        $csrf = $request->param('csrf');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $collection = models\Collection::find($collection_id);
        if (!auth\CollectionsAccess::canDelete($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\CSRF::validate($csrf)) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        models\Collection::delete($collection->id);

        return Response::redirect('collections');
    }
}
