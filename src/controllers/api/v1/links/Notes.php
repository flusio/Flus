<?php

namespace App\controllers\api\v1\links;

use App\auth;
use App\controllers\api\v1\BaseController;
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
     * @request_param string link_id
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot list the notes.
     * @response 404
     *     If the link does not exist.
     * @response 200
     */
    public function index(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $link_id = $request->parameters->getString('link_id', '');

        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::json(404, [
                'error' => 'The link does not exist.',
            ]);
        }

        if (!auth\LinksAccess::canView($user, $link)) {
            return Response::json(403, [
                'error' => 'You cannot list the notes of the link.',
            ]);
        }

        return Response::json(200, array_map(function ($note): array {
            return $note->toJson();
        }, $link->notes()));
    }

    /**
     * @request_param string link_id
     * @request_param string content
     *
     * @response 400
     *     If the content is invalid.
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 403
     *     If the user cannot add notes to the link.
     * @response 404
     *     If the link does not exist.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $link_id = $request->parameters->getString('link_id', '');

        $json_request = $this->toJsonRequest($request);
        $content = $json_request->parameters->getString('content', '');

        $link = models\Link::find($link_id);

        if (!$link) {
            return Response::json(404, [
                'error' => 'The link does not exist.',
            ]);
        }

        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::json(403, [
                'error' => 'You cannot add notes to the link.',
            ]);
        }

        $note = new models\Note($user->id, $link->id, $content);
        if (!$note->validate()) {
            return $this->badRequest($note->errors(format: false));
        }

        $note->save();
        $link->refreshTags();

        return Response::json(200, $note->toJson());
    }
}
