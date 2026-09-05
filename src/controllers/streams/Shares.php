<?php

namespace App\controllers\streams;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

class Shares extends BaseController
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
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        return Response::ok('streams/shares/index.html.twig', [
            'stream' => $stream,
            'form' => new forms\streams\ShareStream(options: [
                'stream' => $stream,
            ]),
        ]);
    }

    /**
     * @request_param string id
     * @request_param string user_id
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
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
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $form = new forms\streams\ShareStream(options: [
            'stream' => $stream,
        ]);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('streams/shares/index.html.twig', [
                'stream' => $stream,
                'form' => $form,
            ]);
        }

        $stream->shareWith($form->user());

        return Response::ok('streams/shares/index.html.twig', [
            'stream' => $stream,
            'form' => new forms\streams\ShareStream(options: [
                'stream' => $stream,
            ]),
        ]);
    }

    /**
     * @request_param string id
     * @request_param string user_id
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
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
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $form = new forms\streams\UnshareStream();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::badRequest('streams/shares/index.html.twig', [
                'stream' => $stream,
                'form' => new forms\streams\ShareStream(options: [
                    'stream' => $stream,
                ]),
            ]);
        }

        $stream->unshareWith($form->user());

        return Response::ok('streams/shares/index.html.twig', [
            'stream' => $stream,
            'form' => new forms\streams\ShareStream(options: [
                'stream' => $stream,
            ]),
        ]);
    }
}
