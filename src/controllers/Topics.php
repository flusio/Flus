<?php

namespace App\controllers;

use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Topics extends BaseController
{
    /**
     * Show the discovery page.
     *
     * @request_param string id
     * @request_param integer page
     *
     * @response 200
     *     On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the topic doesn't exist.
     * @throws utils\PaginationOutOfBoundsError
     *     If the requested page is out of the pagination bounds.
     */
    public function show(Request $request): Response
    {
        $topic = models\Topic::requireFromRequest($request);
        $page = $request->parameters->getInteger('page', 1);

        $number_collections = $topic->countPublicCollections();

        $pagination = new utils\Pagination($number_collections, 30, $page);

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
