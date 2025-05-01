<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Topics extends BaseController
{
    /**
     * Show the discovery page
     *
     * @request_param string id
     * @request_param integer page
     *
     * @response 302 /topic/:id?page=:bounded_page if :page is invalid
     * @response 404 if the topic is missing
     * @response 200
     */
    public function show(Request $request): Response
    {
        $id = $request->parameters->getString('id', '');
        $pagination_page = $request->parameters->getInteger('page', 1);

        $topic = models\Topic::find($id);
        if (!$topic) {
            return Response::notFound('not_found.phtml');
        }

        $number_collections = $topic->countPublicCollections();

        $pagination = new utils\Pagination($number_collections, 30, $pagination_page);
        if ($pagination_page !== $pagination->currentPage()) {
            return Response::redirect('topic', [
                'id' => $topic->id,
                'page' => $pagination->currentPage(),
            ]);
        }

        $collections = models\Collection::listPublicByTopicIdWithNumberLinks(
            $topic->id,
            $pagination->currentOffset(),
            $pagination->numberPerPage()
        );
        $collections = utils\Sorter::localeSort($collections, 'name');

        return Response::ok('topics/show.phtml', [
            'topic' => $topic,
            'collections' => $collections,
            'pagination' => $pagination,
        ]);
    }
}
