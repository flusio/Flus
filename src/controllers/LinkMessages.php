<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * Handle the requests related to the messages.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkMessages
{
    /**
     * @request_param string link_id
     *
     * @response 302 /links/:id
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function index($request)
    {
        $link_id = $request->param('link_id');
        return Response::redirect('link', ['id' => $link_id]);
    }

    /**
     * Create a message attached to a link.
     *
     * @request_param string link_id
     * @request_param string csrf
     * @request_param string content
     *
     * @response 302 /login?redirect_to=/links/:link_id if not connected
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 400 if csrf or content is invalid
     * @response 302 /links/:link_id
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
     */
    public function create($request)
    {
        $user = utils\CurrentUser::get();
        $link_id = $request->param('link_id');
        $content = $request->param('content');

        if (!$user) {
            return Response::redirect('link', ['id' => $link_id]);
        }

        $link = $user->link($link_id);
        if (!$link) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            $collections = $link->collections();
            models\Collection::sort($collections, $user->locale);
            return Response::badRequest('links/show.phtml', [
                'link' => $link,
                'collections' => $collections,
                'messages' => $link->messages(),
                'comment' => $content,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $message = models\Message::init($user->id, $link->id, $content);
        $errors = $message->validate();
        if ($errors) {
            $collections = $link->collections();
            models\Collection::sort($collections, $user->locale);
            return Response::badRequest('links/show.phtml', [
                'link' => $link,
                'collections' => $collections,
                'messages' => $link->messages(),
                'comment' => $content,
                'errors' => $errors,
            ]);
        }

        $message->save();

        return Response::redirect('link', ['id' => $link_id]);
    }
}
