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
class Discovery
{
    /**
     * Show the discovery page
     *
     * @request_param integer page
     *
     * @response 302 /login?redirect_to=/collections/discover if not connected
     * @response 302 /collections/discover?page=:bounded_page if :page is invalid
     * @response 200
     */
    public function show($request)
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('discover collections'),
            ]);
        }

        $number_collections = models\Collection::daoCall('countForDiscovering', $user->id);

        $pagination_page = intval($request->param('page', 1));
        $pagination = new utils\Pagination($number_collections, 30, $pagination_page);
        if ($pagination_page !== $pagination->currentPage()) {
            return Response::redirect('discover collections', ['page' => $pagination->currentPage()]);
        }

        $collections = models\Collection::daoToList(
            'listForDiscovering',
            $user->id,
            $pagination->currentOffset(),
            $pagination->numberPerPage()
        );
        models\Collection::sort($collections, $user->locale);

        return Response::ok('collections/discovery/show.phtml', [
            'collections' => $collections,
            'pagination' => $pagination,
        ]);
    }
}
