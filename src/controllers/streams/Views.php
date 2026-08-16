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
class Views extends BaseController
{
    /**
     * @request_param string id
     *
     * The request can also carry the string filters parameters (at, days,
     * source, status, with_dismissed, q): they are then used to set the
     * parameters of the new view instead of the default ones.
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
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $view = new models\View($user);
        $view->setStream($stream);
        $view->loadUrlParameters($request->parameters);

        $form = new forms\views\View();

        return Response::ok('streams/views/new.html.twig', [
            'stream' => $stream,
            'url_parameters' => $view->current_url_parameters,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string name
     * @request_param string at
     * @request_param string days
     * @request_param string source
     * @request_param string status
     * @request_param string with_dismissed
     * @request_param string q
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of name or csrf_token is invalid.
     * @response 302 /streams/:id?view=:view_id
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

        $view = new models\View($user);
        $view->setStream($stream);
        $view->loadUrlParameters($request->parameters);

        $form = new forms\views\View(model: $view);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('streams/views/new.html.twig', [
                'stream' => $stream,
                'url_parameters' => $view->current_url_parameters,
                'form' => $form,
            ]);
        }

        $view = $form->model();
        $view->saveParameters();

        return Response::redirect('stream', [
            'id' => $stream->id,
            'view' => $view->id,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string view_id
     *     The id of the view, or "default" to save the default view for the
     *     first time.
     * @request_param string at
     * @request_param string days
     * @request_param string source
     * @request_param string status
     * @request_param string with_dismissed
     * @request_param string q
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash notification.error
     *     If the CSRF token is invalid, or if the view to be created is
     *     invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream or the view don't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function save(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $view = $this->requireView($request, $stream, $user);

        $from = utils\RequestHelper::from($request);

        $form = new forms\views\SaveView();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $view->loadUrlParameters($request->parameters);

        // Saving the default view for the first time creates its row: it must
        // be validated as its name may conflict with an existing view.
        if (!$view->isPersisted() && !$view->validate()) {
            utils\Notification::error(implode(' ', $view->errors()));
            return Response::found($from);
        }

        $view->saveParameters();

        return Response::found($from);
    }

    /**
     * @request_param string id
     * @request_param string view_id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream or the view don't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $view = $this->requireView($request, $stream, $user);

        $form = new forms\views\View(model: $view);

        return Response::ok('streams/views/edit.html.twig', [
            'stream' => $stream,
            'view' => $view,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string view_id
     * @request_param string name
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
     *     If the stream or the view don't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $view = $this->requireView($request, $stream, $user);

        $form = new forms\views\View(model: $view);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('streams/views/edit.html.twig', [
                'stream' => $stream,
                'view' => $view,
                'form' => $form,
            ]);
        }

        $view = $form->model();
        $view->save();

        return Response::found(utils\RequestHelper::from($request));
    }

    /**
     * @request_param string id
     * @request_param string view_id
     * @request_param string csrf_token
     *
     * @response 302 /streams/:id
     *     On success.
     * @response 302 :from
     * @flash notification.error
     *     If the CSRF token is invalid.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream or the view don't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the stream.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'update', $stream);

        $view = $this->requireView($request, $stream, $user);

        $from = utils\RequestHelper::from($request);

        $form = new forms\views\DeleteView();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $view->remove();

        if ($view->is_default) {
            utils\Notification::success(_('The default view has been reset.'));
        } else {
            utils\Notification::success(_('The view has been deleted.'));
        }

        return Response::redirect('stream', ['id' => $stream->id]);
    }

    /**
     * Load the view from the "view_id" parameter, making sure it belongs to
     * the given stream. The "default" id returns the default view of the
     * stream, which may not be persisted yet.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the view doesn't exist, or is attached to another stream.
     */
    private function requireView(
        Request $request,
        models\Stream $stream,
        models\User $user,
    ): models\View {
        $view_id = $request->parameters->getString('view_id', '');

        // The default view can be targeted before having a row in database:
        // saving or renaming it is then what creates the row, with the
        // default parameters.
        if ($view_id === 'default') {
            return models\View::findOrBuildDefaultForStream($stream, $user);
        }

        $view = models\View::requireFromRequest($request, parameter: 'view_id');

        if ($view->stream_id !== $stream->id) {
            throw new \Minz\Errors\MissingRecordError('The view is not attached to this stream.');
        }

        $view->setStream($stream);

        return $view;
    }
}
