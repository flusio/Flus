<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Topics
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
    public function show($request)
    {
        $id = $request->param('id');
        $pagination_page = $request->paramInteger('page', 1);

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

        $collections = models\Collection::daoToList(
            'listPublicByTopicIdWithNumberLinks',
            $topic->id,
            $pagination->currentOffset(),
            $pagination->numberPerPage()
        );
        utils\Sorter::localeSort($collections, 'name');

        return Response::ok('topics/show.phtml', [
            'topic' => $topic,
            'collections' => $collections,
            'pagination' => $pagination,
        ]);
    }
}
