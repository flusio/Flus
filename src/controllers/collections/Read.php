<?php

namespace flusio\controllers\collections;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Read
{
    /**
     * Show the read page
     *
     * @request_param integer page
     *
     * @response 302 /login?redirect_to=/read
     *     if not connected
     * @response 302 /read?page=:bounded_page
     *     if page is invalid
     * @response 200
     */
    public function index($request)
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('read list'),
            ]);
        }

        $read_list = $user->readList();
        $number_links = models\Link::daoCall('countByCollectionId', $read_list->id, false);
        $pagination_page = $request->paramInteger('page', 1);
        $number_per_page = 30;
        $pagination = new utils\Pagination($number_links, $number_per_page, $pagination_page);
        if ($pagination_page !== $pagination->currentPage()) {
            return Response::redirect('read list', [
                'page' => $pagination->currentPage(),
            ]);
        }

        return Response::ok('read/index.phtml', [
            'collection' => $read_list,
            'links' => $read_list->links(
                $pagination->currentOffset(),
                $pagination->numberPerPage()
            ),
            'pagination' => $pagination,
        ]);
    }
}