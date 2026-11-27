<?php

namespace App\controllers\streams;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;
use Minz\Template\TwigExtension;

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

        models\streams\Preloader::for([$stream])->shares();

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
            models\streams\Preloader::for([$stream])->shares();

            return Response::badRequest('streams/shares/index.html.twig', [
                'stream' => $stream,
                'form' => $form,
            ]);
        }

        $stream->shareWith($form->user());

        models\streams\Preloader::for([$stream])->shares();

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
            models\streams\Preloader::for([$stream])->shares();

            return Response::badRequest('streams/shares/index.html.twig', [
                'stream' => $stream,
                'form' => new forms\streams\ShareStream(options: [
                    'stream' => $stream,
                ]),
            ]);
        }

        $stream->unshareWith($form->user());

        models\streams\Preloader::for([$stream])->shares();

        return Response::ok('streams/shares/index.html.twig', [
            'stream' => $stream,
            'form' => new forms\streams\ShareStream(options: [
                'stream' => $stream,
            ]),
        ]);
    }

    /**
     * Remove the access of the current user to the stream.
     *
     * @request_param string id
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 /streams/:id
     * @flash notification.success
     *     On success, if the user can still view the stream.
     * @response 302 /journal
     * @flash notification.success
     *     On success, otherwise.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the stream is not shared with the user.
     */
    public function leave(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'leave', $stream);

        $form = new forms\streams\LeaveStream();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found(utils\RequestHelper::from($request));
        }

        $stream->unshareWith($user);

        utils\Notification::success(TwigExtension::translate(
            'You left the stream “%s”.',
            [$stream->name()],
        ));

        if (auth\Access::can($user, 'view', $stream)) {
            return Response::redirect('stream', ['id' => $stream->id]);
        }

        return Response::redirect('journal');
    }
}
