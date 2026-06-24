<?php

namespace App\controllers\streams;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
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
     *     If the user cannot update the stream.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $suggested_sources = $user->followedCollections(['number_links']);
        $suggested_sources = utils\Sorter::localeSort($suggested_sources, 'name');

        $existing_sources = $stream->sources();

        $suggested_sources = array_udiff(
            $suggested_sources,
            $existing_sources,
            function (models\Collection $source1, models\Collection $source2): int {
                return $source1->id <=> $source2->id;
            },
        );

        return Response::ok('streams/sources/edit.html.twig', [
            'stream' => $stream,
            'suggested_sources' => $suggested_sources,
        ]);
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
