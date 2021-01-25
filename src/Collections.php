<?php

namespace flusio;

use Minz\Response;

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
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function index()
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('collections'),
            ]);
        }

        $collections = $user->collectionsWithNumberLinks();
        $followed_collections = $user->followedCollectionsWithNumberLinks();
        models\Collection::sort($collections, $user->locale);
        models\Collection::sort($followed_collections, $user->locale);

        return Response::ok('collections/index.phtml', [
            'collections' => $collections,
            'followed_collections' => $followed_collections,
        ]);
    }

    /**
     * Show the page to create a collection
     *
     * @response 302 /login?redirect_to=/collections/new if not connected
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function new()
    {
        $user = utils\CurrentUser::get();
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
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function create($request)
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('new collection'),
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $collections_to_topics_dao = new models\dao\CollectionsToTopics();
        $topic_dao = new models\dao\Topic();

        $name = $request->param('name', '');
        $description = $request->param('description', '');
        $topic_ids = $request->param('topic_ids', []);
        $is_public = $request->param('is_public', false);

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('collections/new.phtml', [
                'name' => $name,
                'description' => $description,
                'topic_ids' => $topic_ids,
                'is_public' => $is_public,
                'topics' => $topics,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($topic_ids && !$topic_dao->exists($topic_ids)) {
            return Response::badRequest('collections/new.phtml', [
                'name' => $name,
                'description' => $description,
                'topic_ids' => $topic_ids,
                'is_public' => $is_public,
                'topics' => $topics,
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
                'errors' => $errors,
            ]);
        }

        $collection_id = $collection_dao->save($collection);
        if ($topic_ids) {
            $collections_to_topics_dao->attach($collection_id, $topic_ids);
        }

        return Response::redirect('collection', ['id' => $collection_id]);
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
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function show($request)
    {
        $user = utils\CurrentUser::get();
        $collection_id = $request->param('id');
        $collection_dao = new models\dao\Collection();
        $db_collection = $collection_dao->find($collection_id);

        $is_connected = $user !== null;
        $is_owned = $is_connected && $db_collection && $user->id === $db_collection['user_id'];
        $is_public = $db_collection && $db_collection['is_public'];

        if ($is_connected && $is_owned) {
            $collection = new models\Collection($db_collection);
            $topics = $collection->topics();
            models\Topic::sort($topics, $user->locale);
            return Response::ok('collections/show.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'links' => $collection->links(),
            ]);
        }

        if ($is_public) {
            $collection = new models\Collection($db_collection);
            $topics = $collection->topics();
            models\Topic::sort($topics, utils\Locale::currentLocale());
            return Response::ok('collections/show_public.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'links' => $collection->publicLinks(),
            ]);
        }

        if ($is_connected) {
            return Response::notFound('not_found.phtml');
        } else {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('collection', ['id' => $collection_id]),
            ]);
        }
    }

    /**
     * Show the edition page of a collection
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/collection/:id/edit if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function edit($request)
    {
        $user = utils\CurrentUser::get();
        $collection_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit collection', ['id' => $collection_id]),
            ]);
        }

        $collection = $user->collection($collection_id);
        if ($collection) {
            $topics = models\Topic::listAll();
            models\Topic::sort($topics, $user->locale);

            return Response::ok('collections/edit.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'name' => $collection->name,
                'description' => $collection->description,
                'is_public' => $collection->is_public,
                'topic_ids' => array_column($collection->topics(), 'id'),
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
     *
     * @response 302 /login?redirect_to=/collection/:id/edit if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 400 if csrf, name or topic_ids are invalid
     * @response 302 /collections/:id
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function update($request)
    {
        $user = utils\CurrentUser::get();
        $collection_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit collection', ['id' => $collection_id]),
            ]);
        }

        $collection = $user->collection($collection_id);
        if (!$collection) {
            return Response::notFound('not_found.phtml');
        }

        $topic_dao = new models\dao\Topic();
        $collection_dao = new models\dao\Collection();
        $collections_to_topics_dao = new models\dao\CollectionsToTopics();

        $topics = models\Topic::listAll();
        models\Topic::sort($topics, $user->locale);

        $name = $request->param('name', '');
        $description = $request->param('description', '');
        $is_public = $request->param('is_public', false);
        $topic_ids = $request->param('topic_ids', []);

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('collections/edit.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'name' => $name,
                'description' => $description,
                'is_public' => $is_public,
                'topic_ids' => $topic_ids,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($topic_ids && !$topic_dao->exists($topic_ids)) {
            return Response::badRequest('collections/edit.phtml', [
                'collection' => $collection,
                'topics' => $topics,
                'name' => $name,
                'description' => $description,
                'is_public' => $is_public,
                'topic_ids' => $topic_ids,
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
                'errors' => $errors,
            ]);
        }

        $collection_dao->save($collection);
        $collections_to_topics_dao->set($collection_id, $topic_ids);

        return Response::redirect('collection', ['id' => $collection->id]);
    }

    /**
     * Delete a collection
     *
     * @request_param string id
     * @request_param string from default is /collections/:id/edit
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 302 :from if csrf is invalid
     * @response 302 /collections
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function delete($request)
    {
        $user = utils\CurrentUser::get();
        $collection_id = $request->param('id');
        $from = $request->param('from', \Minz\Url::for('edit collection', ['id' => $collection_id]));

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $collection = $user->collection($collection_id);
        if (!$collection) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $collection_dao = new models\dao\Collection();
        $collection_dao->delete($collection->id);

        return Response::redirect('collections');
    }

    /**
     * Show the bookmarks page
     *
     * @response 302 /login?redirect_to=/bookmarks if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function showBookmarks()
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('bookmarks'),
            ]);
        }

        $bookmarks = $user->bookmarks();
        if (!$bookmarks) {
            \Minz\Log::error("User {$user->id} has no Bookmarks collection.");
            return Response::notFound('not_found.phtml', [
                'details' => _('It looks like you have no “Bookmarks” collection, you should contact the support.'),
            ]);
        }

        return Response::ok('collections/show_bookmarks.phtml', [
            'collection' => $bookmarks,
            'links' => $bookmarks->links(),
        ]);
    }

    /**
     * Show the discovering page
     *
     * @request_param integer page
     *
     * @response 302 /login?redirect_to=/collections/discover if not connected
     * @response 302 /collections/discover?page=:bounded_page if :page is invalid
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function discover($request)
    {
        $user = utils\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('discover collections'),
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $number_collections = $collection_dao->countForDiscovering($user->id);

        $pagination_page = intval($request->param('page', 1));
        $pagination = new utils\Pagination($number_collections, 30, $pagination_page);
        if ($pagination_page !== $pagination->currentPage()) {
            return Response::redirect('discover collections', ['page' => $pagination->currentPage()]);
        }

        $db_collections = $collection_dao->listForDiscovering(
            $user->id,
            $pagination->currentOffset(),
            $pagination->numberPerPage()
        );
        $collections = [];
        foreach ($db_collections as $db_collection) {
            $collections[] = new models\Collection($db_collection);
        }
        models\Collection::sort($collections, $user->locale);

        return Response::ok('collections/discover.phtml', [
            'collections' => $collections,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Make the current user following the given collection
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/collections/:id
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or user hasn't access
     * @response 302 /collections/:id
     *     if CSRF is invalid
     * @response 302 /collections/:id
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function follow($request)
    {
        $user = utils\CurrentUser::get();
        $collection_id = $request->param('id');
        $collection_dao = new models\dao\Collection();

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('collection', ['id' => $collection_id]),
            ]);
        }

        $db_collection = $collection_dao->find($collection_id);
        if (!$db_collection) {
            return Response::notFound('not_found.phtml');
        }

        $collection = new models\Collection($db_collection);
        if (!$collection->is_public) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::redirect('collection', ['id' => $collection->id]);
        }

        $is_following = $user->isFollowing($collection->id);
        if (!$is_following) {
            $user->follow($collection->id);
        }

        return Response::redirect('collection', ['id' => $collection->id]);
    }

    /**
     * Make the current user following the given collection
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/collections/:id
     *     if not connected
     * @response 404
     *     if the collection doesn’t exist or user hasn't access
     * @response 302 /collections/:id
     *     if CSRF is invalid
     * @response 302 /collections/:id
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function unfollow($request)
    {
        $user = utils\CurrentUser::get();
        $collection_id = $request->param('id');
        $collection_dao = new models\dao\Collection();

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('collection', ['id' => $collection_id]),
            ]);
        }

        $db_collection = $collection_dao->find($collection_id);
        if (!$db_collection) {
            return Response::notFound('not_found.phtml');
        }

        $collection = new models\Collection($db_collection);
        if (!$collection->is_public) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            utils\Flash::set('error', _('A security verification failed: you should retry to submit the form.'));
            return Response::redirect('collection', ['id' => $collection->id]);
        }

        $is_following = $user->isFollowing($collection->id);
        if ($is_following) {
            $user->unfollow($collection->id);
        }

        return Response::redirect('collection', ['id' => $collection->id]);
    }
}
