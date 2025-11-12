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
class Read extends BaseController
{
    /**
     * Show the read page.
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

        $read_list = $user->readList();
        $page = $request->parameters->getInteger('page', 1);

        $number_links = models\Link::countByCollectionId($read_list->id);

        $pagination = new utils\Pagination($number_links, 30, $page);

        $links = $read_list->links(
            ['published_at', 'number_notes'],
            [
                'offset' => $pagination->currentOffset(),
                'limit' => $pagination->numberPerPage(),
            ]
        );

        return Response::ok('read/index.phtml', [
            'collection' => $read_list,
            'links' => $links,
            'pagination' => $pagination,
        ]);
    }
}
