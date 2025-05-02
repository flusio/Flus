<?php

namespace App\controllers\api\v1;

use App\auth;
use App\forms\api as forms;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Notes extends BaseController
{
    /**
     * @request_param string id
     * @json_param string content
     *
     * @response 400
     *     If the content is invalid.
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot update the note.
     * @response 404
     *     If the note does not exist.
     * @response 200
     */
    public function update(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $json_request = $this->toJsonRequest($request);

        $note_id = $request->parameters->getString('id', '');
        $note = models\Note::find($note_id);

        if (!$note) {
            return Response::json(404, [
                'error' => 'The note does not exist.',
            ]);
        }

        if (!auth\NotesAccess::canUpdate($user, $note)) {
            return Response::json(403, [
                'error' => 'You cannot update the note.',
            ]);
        }

        $form = new forms\Note(model: $note);
        $form->handleRequest($json_request);

        if (!$form->validate()) {
            return $this->badRequest($form->errors(format: false));
        }

        $note = $form->model();
        $note->save();
        $note->link()->refreshTags();

        return Response::json(200, $note->toJson());
    }

    /**
     * @request_param string id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot delete the note.
     * @response 404
     *     If the note does not exist.
     * @response 200
     */
    public function delete(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $note_id = $request->parameters->getString('id', '');
        $note = models\Note::find($note_id);

        if (!$note) {
            return Response::json(404, [
                'error' => 'The note does not exist.',
            ]);
        }

        if (!auth\NotesAccess::canDelete($user, $note)) {
            return Response::json(403, [
                'error' => 'You cannot delete the note.',
            ]);
        }

        $link = $note->link();
        $note->remove();
        $link->refreshTags();

        return Response::json(200, []);
    }
}
