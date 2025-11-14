<?php

namespace App\controllers\links;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\jobs;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * Handle the requests related to the notes.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Notes extends BaseController
{
    /**
     * @request_param string link_id
     *
     * @response 302 /links/:id
     */
    public function index(Request $request): Response
    {
        $link_id = $request->parameters->getString('link_id', '');
        return Response::redirect('link', ['id' => $link_id]);
    }

    /**
     * Create a note attached to a link.
     *
     * @request_param string link_id
     * @request_param string content
     * @request_param boolean share_on_mastodon
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /links/:link_id
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the link.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request, parameter: 'link_id');

        auth\Access::require($user, 'update', $link);

        $note = $link->initNote();
        $form = new forms\notes\NewNote(model: $note, options: [
            'enable_mastodon' => $user->isMastodonEnabled(),
        ]);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/show.phtml', [
                'link' => $link,
                'form' => $form,
            ]);
        }

        $note = $form->model();
        $note->save();

        $link->refreshTags();

        if ($form->shouldShareOnMastodon()) {
            $share_on_mastodon_job = new jobs\ShareOnMastodon();
            $share_on_mastodon_job->performAsap($user->id, $link->id, $note->id);
        }

        return Response::redirect('link', ['id' => $link->id]);
    }
}
