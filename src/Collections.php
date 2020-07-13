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

        $collection_dao = new models\dao\Collection();
        $db_collections = $collection_dao->listWithNumberLinksForUser($user->id);

        $collections = [];
        foreach ($db_collections as $db_collection) {
            $collections[] = new models\Collection($db_collection);
        }

        $collator = new \Collator($user->locale);
        usort($collections, function ($collection1, $collection2) use ($collator) {
            return $collator->compare($collection1->name, $collection2->name);
        });

        return Response::ok('collections/index.phtml', [
            'collections' => $collections,
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
        if (!utils\CurrentUser::get()) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('new collection'),
            ]);
        }

        return Response::ok('collections/new.phtml', [
            'name' => '',
            'description' => '',
        ]);
    }

    /**
     * Create a collection
     *
     * @request_param string csrf
     * @request_param string name
     * @request_param string description
     *
     * @response 302 /login?redirect_to=/collections/new if not connected
     * @response 400 if csrf or name are invalid
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

        $name = $request->param('name', '');
        $description = $request->param('description', '');

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('collections/new.phtml', [
                'name' => $name,
                'description' => $description,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $collection = models\Collection::init($user->id, $name, $description);
        $errors = $collection->validate();
        if ($errors) {
            return Response::badRequest('collections/new.phtml', [
                'name' => $name,
                'description' => $description,
                'errors' => $errors,
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $collection_id = $collection_dao->save($collection);

        return Response::redirect('collection', ['id' => $collection_id]);
    }

    /**
     * Show a collection page
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/collection/:id if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 200
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function show($request)
    {
        $current_user = utils\CurrentUser::get();
        $id = $request->param('id');
        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('collection', ['id' => $id]),
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $link_dao = new models\dao\Link();
        $db_collection = $collection_dao->findBy([
            'id' => $id,
            'user_id' => $current_user->id,
            'type' => 'collection',
        ]);
        if (!$db_collection) {
            return Response::notFound('not_found.phtml');
        }

        $collection = new models\Collection($db_collection);

        $links = [];
        $db_links = $link_dao->listByCollectionId($collection->id);
        foreach ($db_links as $db_link) {
            $links[] = new models\Link($db_link);
        }

        return Response::ok('collections/show.phtml', [
            'collection' => $collection,
            'links' => $links,
        ]);
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
        $id = $request->param('id');
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit collection', ['id' => $id]),
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $db_collection = $collection_dao->findBy([
            'id' => $id,
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        if (!$db_collection) {
            return Response::notFound('not_found.phtml');
        }

        $collection = new models\Collection($db_collection);
        return Response::ok('collections/edit.phtml', [
            'collection' => $collection,
            'name' => $collection->name,
            'description' => $collection->description,
        ]);
    }

    /**
     * Update a collection
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/collection/:id/edit if not connected
     * @response 404 if the collection doesn’t exist or user hasn't access
     * @response 400 if csrf or name are invalid
     * @response 302 /collections/:id
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function update($request)
    {
        $user = utils\CurrentUser::get();
        $id = $request->param('id');
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('edit collection', ['id' => $id]),
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $db_collection = $collection_dao->findBy([
            'id' => $id,
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        if (!$db_collection) {
            return Response::notFound('not_found.phtml');
        }

        $collection = new models\Collection($db_collection);
        $name = $request->param('name', '');
        $description = $request->param('description', '');

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('collections/edit.phtml', [
                'collection' => $collection,
                'name' => $name,
                'description' => $description,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $collection->name = trim($name);
        $collection->description = trim($description);
        $errors = $collection->validate();
        if ($errors) {
            return Response::badRequest('collections/edit.phtml', [
                'collection' => $collection,
                'name' => $name,
                'description' => $description,
                'errors' => $errors,
            ]);
        }

        $collection_dao->save($collection);

        return Response::redirect('collection', ['id' => $collection->id]);
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
        $current_user = utils\CurrentUser::get();
        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('bookmarks'),
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $link_dao = new models\dao\Link();
        $db_bookmarks_collection = $collection_dao->findBy([
            'user_id' => $current_user->id,
            'type' => 'bookmarks',
        ]);
        if (!$db_bookmarks_collection) {
            \Minz\Log::error("User {$current_user->id} has no Bookmarks collection.");
            return Response::notFound('not_found.phtml', [
                'details' => _('It looks like you have no “Bookmarks” collection, you should contact the support.'),
            ]);
        }

        $bookmarks_collection = new models\Collection($db_bookmarks_collection);

        $links = [];
        $db_links = $link_dao->listByCollectionId($bookmarks_collection->id);
        foreach ($db_links as $db_link) {
            $links[] = new models\Link($db_link);
        }

        return Response::ok('collections/show_bookmarks.phtml', [
            'collection' => $bookmarks_collection,
            'links' => $links,
        ]);
    }
}
