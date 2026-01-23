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
class Explore extends BaseController
{
    /**
     * Show the explore page
     *
     * @request_param string topic
     * @request_param integer page
     *
     * @response 200
     *     On success.
     *
     * @throws utils\PaginationOutOfBoundsError
     *     If the requested page is out of the pagination bounds.
     */
    public function show(Request $request): Response
    {
        $topics = models\Topic::listAll();
        $topics = utils\Sorter::localeSort($topics, 'label');

        if (!$topics) {
            return Response::ok('explore/disabled.html.twig');
        }

        $topic = models\Topic::loadFromRequest($request, parameter: 'topic');
        $page = $request->parameters->getInteger('page', 1);

        if (!$topic) {
            $topic = $topics[0];
        }

        $number_collections = $topic->countPublicCollections();

        $pagination = new utils\Pagination($number_collections, 30, $page);

        $collections = models\Collection::listPublicByTopicIdWithNumberLinks(
            $topic->id,
            $pagination->currentOffset(),
            $pagination->numberPerPage()
        );
        $collections = utils\Sorter::localeSort($collections, 'name');

        return Response::ok('explore/show.html.twig', [
            'topics' => $topics,
            'current_topic' => $topic,
            'collections' => $collections,
            'pagination' => $pagination,
        ]);
    }

    /**
     * @response 302 /explore
     */
    public function discovery(): Response
    {
        return Response::redirect('explore');
    }

    /**
     * @request_param string id
     *
     * @response 302 /explore?topic=:id
     */
    public function topic(Request $request): Response
    {
        return Response::redirect('explore', [
            'topic' => $request->parameters->getString('id', ''),
        ]);
    }
}
