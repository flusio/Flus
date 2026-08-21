<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\utils;
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
     * @request_param bool display_unread_in_sidenav
     * @request_param bool is_public
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
     *     If the stream requires the users to be logged in while they are not.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the stream.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $stream = models\Stream::requireFromRequest($request);

        if ($user) {
            auth\Access::require($user, 'view', $stream);
        } elseif (!auth\Access::can($user, 'view', $stream)) {
            auth\CurrentUser::require();
        }

        $stream_view = models\StreamView::buildFromRequest($stream, $user, $request);

        return Response::ok('streams/show.html.twig', [
            'stream_view' => $stream_view,
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

        $form = new forms\streams\Stream(model: $stream);

        return Response::ok('streams/edit.html.twig', [
            'stream' => $stream,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string name
     * @request_param string description
     * @request_param bool display_unread_in_sidenav
     * @request_param bool is_public
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $form = new forms\streams\Stream(model: $stream);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('streams/edit.html.twig', [
                'stream' => $stream,
                'form' => $form,
            ]);
        }

        $stream = $form->model();
        $stream->save();

        return Response::found(utils\RequestHelper::from($request));
    }

    /**
     * @request_param string id
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash notification.error
     *     If the CSRF token is invalid.
     * @response 302 /news
     * @flash notification.success
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot delete the stream.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'delete', $stream);

        $from = utils\RequestHelper::from($request);

        $form = new forms\streams\DeleteStream();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $stream->remove();

        utils\Notification::success(_('The stream has been deleted.'));

        return Response::redirect('news');
    }
}
