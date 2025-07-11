<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Notes extends BaseController
{
    /**
     * Edit a note.
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected.
     * @response 404
     *     If the note doesn’t exist or user hasn't access to it.
     * @response 200
     *     On success.
     */
    public function edit(Request $request): Response
    {
        $note_id = $request->parameters->getString('id', '');
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $note = models\Note::findBy([
            'id' => $note_id,
            'user_id' => $user->id,
        ]);
        if (!$note) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('notes/edit.phtml', [
            'note' => $note,
            'content' => $note->content,
            'from' => $from,
        ]);
    }

    /**
     * Update a note.
     *
     * @request_param string id
     * @request_param string content
     * @request_param string from
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected.
     * @response 404
     *     If the note doesn’t exist or user hasn't access to it.
     * @response 302
     * @flash error
     *     If the CSRF or the content are invalid.
     * @response 302 :from
     *     On success.
     */
    public function update(Request $request): Response
    {
        $note_id = $request->parameters->getString('id');
        $content = $request->parameters->getString('content', '');
        $from = $request->parameters->getString('from', '');
        $csrf = $request->parameters->getString('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $note = models\Note::findBy([
            'id' => $note_id,
            'user_id' => $user->id,
        ]);
        if (!$note) {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        $note->content = trim($content);

        if (!$note->validate()) {
            \Minz\Flash::set('errors', $note->errors());
            return Response::found($from);
        }

        $note->save();

        $note->link()->refreshTags();

        return Response::found($from);
    }

    /**
     * Delete a note
     *
     * @request_param string id
     * @request_param string redirect_to default is /
     *
     * @response 302 /login?redirect_to=:redirect_to if not connected
     * @response 404 if the note doesn’t exist or user hasn't access
     * @response 302 :redirect_to if csrf is invalid
     * @response 302 :redirect_to on success
     */
    public function delete(Request $request): Response
    {
        $note_id = $request->parameters->getString('id', '');
        $redirect_to = $request->parameters->getString('redirect_to', \Minz\Url::for('home'));
        $csrf = $request->parameters->getString('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $redirect_to);

        $note = models\Note::findBy([
            'id' => $note_id,
            'user_id' => $user->id,
        ]);

        if (!$note) {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($redirect_to);
        }

        $link = $note->link();

        $note->remove();

        $link->refreshTags();

        return Response::found($redirect_to);
    }
}
