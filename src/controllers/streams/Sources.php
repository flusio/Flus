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
    /**
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the stream.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'view', $stream);

        return Response::ok('streams/sources/index.html.twig', [
            'stream' => $stream,
        ]);
    }

    /**
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $followed_sources = $user->followedSources();
        $existing_sources = $stream->sources([
            'context_user' => $user,
        ]);

        $suggested_sources = array_udiff(
            $followed_sources,
            $existing_sources,
            function (models\Collection $source1, models\Collection $source2): int {
                return $source1->id <=> $source2->id;
            },
        );

        return Response::ok('streams/sources/edit.html.twig', [
            'stream' => $stream,
            'suggested_sources' => $suggested_sources,
            'focused_source_id' => \Minz\Flash::pop('focused_source_id'),
        ]);
    }

    /**
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function newFeed(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $form = new forms\collections\NewFeed();

        return Response::ok('streams/sources/new_feed.html.twig', [
            'stream' => $stream,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string url
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /streams/:id/sources/edit
     * @flash focused_source_id
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function createFeed(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $form = new forms\collections\NewFeed();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('streams/sources/new_feed.html.twig', [
                'stream' => $stream,
                'form' => $form,
            ]);
        }

        $feed = $form->feed();

        if (!$feed->isPersisted()) {
            $feed_fetcher_service = new services\FeedFetcher([
                'http_timeout' => 10,
                'ignore_rate_limit' => true,
            ]);
            $feed_fetcher_service->fetch($feed);
        }

        $stream->addSource($feed);

        // Let the edit page give the focus to the feed that has just been added.
        \Minz\Flash::set('focused_source_id', $feed->id);

        return Response::redirect('edit stream sources', ['id' => $stream->id]);
    }

    /**
     * @request_param string id
     * @request_param string source_id
     * @request_param string csrf_token
     *
     * @response 302 /streams/:id/sources/edit
     * @flash notification.error
     *     If at least one of the parameters is invalid.
     * @response 302 /streams/:id/sources/edit
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream or the source don't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream or cannot view the source.
     */
    public function add(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $stream = models\Stream::requireFromRequest($request);
        $source = models\Collection::requireFromRequest($request, parameter: 'source_id');

        auth\Access::require($user, 'update', $stream);
        auth\Access::require($user, 'view', $source);

        $form = new forms\streams\AddSource();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::redirect('edit stream sources', ['id' => $stream->id]);
        }

        $stream->addSource($source);

        return Response::redirect('edit stream sources', ['id' => $stream->id]);
    }

    /**
     * @request_param string id
     * @request_param string source_id
     * @request_param string csrf_token
     *
     * @response 302 /streams/:id/sources/edit
     * @flash notification.error
     *     If at least one of the parameters is invalid.
     * @response 302 /streams/:id/sources/edit
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream or the source don't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function remove(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $stream = models\Stream::requireFromRequest($request);
        $source = models\Collection::requireFromRequest($request, parameter: 'source_id');

        auth\Access::require($user, 'update', $stream);

        $form = new forms\streams\RemoveSource();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::redirect('edit stream sources', ['id' => $stream->id]);
        }

        $stream->removeSource($source);

        return Response::redirect('edit stream sources', ['id' => $stream->id]);
    }
}
