<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Messages
{
    /**
     * Edit a message.
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected.
     * @response 404
     *     If the message doesn’t exist or user hasn't access to it.
     * @response 200
     *     On success.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $message_id = $request->param('id', '');
        $from = $request->param('from', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $message = models\Message::findBy([
            'id' => $message_id,
            'user_id' => $user->id,
        ]);
        if (!$message) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('messages/edit.phtml', [
            'message' => $message,
            'comment' => $message->content,
            'from' => $from,
        ]);
    }

    /**
     * Update a message.
     *
     * @request_param string id
     * @request_param string content
     * @request_param string from
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected.
     * @response 404
     *     If the message doesn’t exist or user hasn't access to it.
     * @response 302
     * @flash error
     *     If the CSRF or the content are invalid.
     * @response 302 :from
     *     On success.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $message_id = $request->param('id');
        $content = $request->param('content', '');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $message = models\Message::findBy([
            'id' => $message_id,
            'user_id' => $user->id,
        ]);
        if (!$message) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $message->content = trim($content);

        $errors = $message->validate();
        if ($errors) {
            \Minz\Flash::set('errors', $errors);
            return Response::found($from);
        }

        $message->save();

        services\LinkTags::refresh($message->link());

        return Response::found($from);
    }

    /**
     * Delete a message
     *
     * @request_param string id
     * @request_param string redirect_to default is /
     *
     * @response 302 /login?redirect_to=:redirect_to if not connected
     * @response 404 if the message doesn’t exist or user hasn't access
     * @response 302 :redirect_to if csrf is invalid
     * @response 302 :redirect_to on success
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $message_id = $request->param('id', '');
        $redirect_to = $request->param('redirect_to', \Minz\Url::for('home'));
        $csrf = $request->param('csrf', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $redirect_to]);
        }

        $message = models\Message::findBy([
            'id' => $message_id,
            'user_id' => $user->id,
        ]);

        if (!$message) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($redirect_to);
        }

        models\Message::delete($message->id);

        services\LinkTags::refresh($message->link());

        return Response::found($redirect_to);
    }
}
