<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Bookmarks
{
    /**
     * Show the bookmarks page
     *
     * @request_param integer page
     *
     * @response 302 /login?redirect_to=/bookmarks if not connected
     * @response 200
     */
    public function index($request)
    {
        $user = auth\CurrentUser::get();
        $page = $request->paramInteger('page', 1);

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('bookmarks'),
            ]);
        }

        $bookmarks = $user->bookmarks();

        $number_links = models\Link::daoCall('countByCollectionId', $bookmarks->id);
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
                    'context_user_id' => $user->id,
                ]
            ),
            'pagination' => $pagination,
        ]);
    }
}
