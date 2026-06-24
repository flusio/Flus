<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Streams extends BaseController
{
    /**
     * @response 200
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function new(): Response
    {
        auth\CurrentUser::require();

        $form = new forms\streams\Stream();

        return Response::ok('streams/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * @request_param string name
     * @request_param string description
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /streams/:id/sources/edit
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $stream = $user->initStream();
        $form = new forms\streams\Stream(model: $stream);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('streams/new.html.twig', [
                'form' => $form,
            ]);
        }

        $stream = $form->model();
        $stream->save();

        return Response::redirect('edit stream sources', ['id' => $stream->id]);
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
     *     If the user cannot view the stream.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'view', $stream);

        $stream_view = models\StreamView::buildFromRequest($stream, $request);

        return Response::ok('streams/show.html.twig', [
            'stream_view' => $stream_view,
        ]);
    }
}
