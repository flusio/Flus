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
class Notes extends BaseController
{
    /**
     * Edit a note.
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the note doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the note.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $note = models\Note::requireFromRequest($request);

        auth\Access::require($user, 'update', $note);

        $form = new forms\notes\EditNote(model: $note);

        return Response::ok('notes/edit.html.twig', [
            'note' => $note,
            'form' => $form,
        ]);
    }

    /**
     * Update a note.
     *
     * @request_param string id
     * @request_param string content
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
     *     If the note doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the note.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $note = models\Note::requireFromRequest($request);

        auth\Access::require($user, 'update', $note);

        $form = new forms\notes\EditNote(model: $note);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('notes/edit.html.twig', [
                'note' => $note,
                'form' => $form,
            ]);
        }

        $note = $form->model();
        $note->save();

        $note->link()->refreshTags();

        return Response::found(utils\RequestHelper::from($request));
    }

    /**
     * Delete a note
     *
     * @request_param string id
     *
     * @response 302 :from
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $note = models\Note::requireFromRequest($request);

        auth\Access::require($user, 'delete', $note);

        $from = utils\RequestHelper::from($request);

        $form = new forms\notes\DeleteNote();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $link = $note->link();

        $note->remove();

        $link->refreshTags();

        return Response::found($from);
    }
}
