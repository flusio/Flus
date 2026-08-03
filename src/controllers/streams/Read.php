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
class Read extends BaseController
{
    /**
     * Mark links of the stream as read.
     *
     * @request_param string id
     * @request_param date at
     * @request_param integer days
     * @request_param string source
     * @request_param string status
     * @request_param string q
     * @request_param datetime before
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the stream.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'view', $stream);

        $from = utils\RequestHelper::from($request);

        $form = new forms\streams\MarkStreamAsRead(options: [
            'stream' => $stream,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $links = $form->links();

        $user->markAsRead($links);

        return Response::found($from);
    }

    /**
     * Mark links of the stream to be read later.
     *
     * @request_param string id
     * @request_param date at
     * @request_param integer days
     * @request_param string source
     * @request_param string status
     * @request_param string q
     * @request_param datetime before
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the stream.
     */
    public function later(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'view', $stream);

        $from = utils\RequestHelper::from($request);

        $form = new forms\streams\MarkStreamAsReadLater(options: [
            'stream' => $stream,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $links = $form->links();

        $user->markAsReadLater($links);

        return Response::found($from);
    }

    /**
     * Dismiss the links of the stream.
     *
     * @request_param string id
     * @request_param date at
     * @request_param integer days
     * @request_param string source
     * @request_param string status
     * @request_param string q
     * @request_param datetime before
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the stream.
     */
    public function dismiss(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $stream = models\Stream::requireFromRequest($request);

        auth\Access::require($user, 'view', $stream);

        $from = utils\RequestHelper::from($request);

        $form = new forms\streams\MarkStreamAsDismissed(options: [
            'stream' => $stream,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $links = $form->links();

        $user->markAsDismissed($links);

        return Response::found($from);
    }
}
