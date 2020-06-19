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
     * Show the bookmarked page
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
        $link_dao = new models\dao\Link();
        $db_bookmarked_collection = $collection_dao->findBy([
            'user_id' => $current_user->id,
            'type' => 'bookmarked',
        ]);
        if (!$db_bookmarked_collection) {
            return Response::notFound('not_found.phtml', [
                'error' => _('It looks like you have no “Bookmarked” collection, you should contact the support.'),
            ]);
        }

        $bookmarked_collection = new models\Collection($db_bookmarked_collection);

        $links = [];
        $db_links = $link_dao->listByCollectionId($bookmarked_collection->id);
        foreach ($db_links as $db_link) {
            $links[] = new models\Link($db_link);
        }

        return Response::ok('collections/show_bookmarked.phtml', [
            'bookmarked_collection' => $bookmarked_collection,
            'links' => $links,
        ]);
    }
}
