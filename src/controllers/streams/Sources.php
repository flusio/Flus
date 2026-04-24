<?php

namespace App\controllers\streams;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\services;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sources extends BaseController
{
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        return Response::ok('streams/sources/index.html.twig', [
            'stream' => $stream,
        ]);
    }

    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        $feeds = $user->followedCollections(['number_links'], [
            'type' => 'feed',
        ]);
        $collections = $user->followedCollections(['number_links'], [
            'type' => 'collection',
        ]);
        $suggested_sources = array_merge($collections, $feeds);
        $suggested_sources = utils\Sorter::localeSort($suggested_sources, 'name');

        $existing_sources = $stream->sources();
        $suggested_sources = array_udiff(
            $suggested_sources,
            $existing_sources,
            function (models\Collection $source1, models\Collection $source2) {
                return $source1->id <=> $source2->id;
            },
        );

        return Response::ok('streams/sources/edit.html.twig', [
            'stream' => $stream,
            'suggested_sources' => $suggested_sources,
        ]);
    }

    public function add(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $stream = models\Stream::requireFromRequest($request);
        $source = models\Collection::requireFromRequest($request, parameter: 'source_id');

        $stream->addSource($source);

        return Response::redirect('edit stream sources', ['id' => $stream->id]);
    }

    public function remove(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $stream = models\Stream::requireFromRequest($request);
        $source = models\Collection::requireFromRequest($request, parameter: 'source_id');

        $stream->removeSource($source);

        return Response::redirect('edit stream sources', ['id' => $stream->id]);
    }
}
