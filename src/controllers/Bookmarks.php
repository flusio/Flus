<?php

namespace App\controllers;

use App\auth;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Bookmarks extends BaseController
{
    /**
     * Show the bookmarks page.
     *
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

        $bookmarks = $user->bookmarks();
        $page = $request->parameters->getInteger('page', 1);

        $number_links = models\Link::countByCollectionId($bookmarks->id);
        $pagination = new utils\Pagination($number_links, 29, $page);

        $links = $bookmarks->links(
            ['published_at', 'number_notes'],
            [
                'offset' => $pagination->currentOffset(),
                'limit' => $pagination->numberPerPage(),
            ]
        );

        return Response::ok('bookmarks/index.phtml', [
            'collection' => $bookmarks,
            'links' => $links,
            'pagination' => $pagination,
        ]);
    }
}
