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
