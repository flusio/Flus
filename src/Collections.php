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
     * Show the bookmarked / read later page
     *
     * @response 200
     * @response 302 /login?redirect_to=/bookmarked if not connected
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function showBookmarked()
    {
        $current_user = utils\CurrentUser::get();
        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show bookmarked'),
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $db_bookmarked_collection = $collection_dao->findBy([
            'user_id' => $current_user->id,
            'type' => 'bookmarked',
        ]);
        if ($db_bookmarked_collection) {
            $bookmarked_collection = new models\Collection($db_bookmarked_collection);
        } else {
            $bookmarked_collection = null;
        }

        return Response::ok('collections/show_bookmarked.phtml', [
            'bookmarked_collection' => $bookmarked_collection,
        ]);
    }

    /**
     * Create the bookmarked / read later collection
     *
     * @response 201 on success
     * @response 302 /bookmarked if the collection already exists
     * @response 302 /login?redirect_to=/bookmarked if not connected
     * @response 400 if CSRF is invalid
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function createBookmarked($request)
    {
        $current_user = utils\CurrentUser::get();
        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show bookmarked'),
            ]);
        }

        $collection_dao = new models\dao\Collection();
        $csrf = new \Minz\CSRF();

        $db_bookmarked_collection = $collection_dao->findBy([
            'user_id' => $current_user->id,
            'type' => 'bookmarked',
        ]);
        if ($db_bookmarked_collection) {
            return Response::redirect('show bookmarked');
        }

        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('collections/show_bookmarked.phtml', [
                'bookmarked_collection' => null,
                'error' => _('A security verification failed: you should retry to click on the button.'),
            ]);
        }

        $bookmarked_collection = models\Collection::initBookmarked($current_user->id);
        $collection_dao->save($bookmarked_collection);

        return Response::created('collections/show_bookmarked.phtml', [
            'bookmarked_collection' => $bookmarked_collection,
        ]);
    }
}
