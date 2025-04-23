<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Bookmarks extends BaseController
{
    /**
     * Show the bookmarks page
     *
     * @request_param integer page
     *
     * @response 302 /login?redirect_to=/bookmarks if not connected
     * @response 200
     */
    public function index(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('bookmarks'));
        $page = $request->paramInteger('page', 1);
        $bookmarks = $user->bookmarks();

        $number_links = models\Link::countByCollectionId($bookmarks->id);
        $pagination = new utils\Pagination($number_links, 29, $page);
        if ($page !== $pagination->currentPage()) {
            return Response::redirect('bookmarks', [
                'page' => $pagination->currentPage(),
            ]);
        }

        return Response::ok('bookmarks/index.phtml', [
            'collection' => $bookmarks,
            'links' => $bookmarks->links(
                ['published_at', 'number_comments'],
                [
                    'offset' => $pagination->currentOffset(),
                    'limit' => $pagination->numberPerPage(),
                ]
            ),
            'pagination' => $pagination,
        ]);
    }
}
