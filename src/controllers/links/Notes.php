<?php

namespace App\controllers\links;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\jobs;
use App\models;
use App\services;

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
     *
     * @param \Minz\Request $request
     *
     * @return \Minz\Response
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
     * @request_param string csrf
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
    public function create(Request $request): Response
    {
        $link_id = $request->parameters->getString('link_id', '');
        $content = $request->parameters->getString('content', '');
        $share_on_mastodon = $request->parameters->getBoolean('share_on_mastodon');
        $csrf = $request->parameters->getString('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('link', ['id' => $link_id]));

        $link = models\Link::find($link_id);
        $can_update = $link && auth\LinksAccess::canUpdate($user, $link);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        $mastodon_configured = models\MastodonAccount::existsBy([
            'user_id' => $user->id,
        ]);

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('links/show.phtml', [
                'link' => $link,
                'can_update' => $can_update,
                'content' => $content,
                'share_on_mastodon' => $share_on_mastodon,
                'mastodon_configured' => $mastodon_configured,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $note = new models\Note($user->id, $link->id, $content);

        if (!$note->validate()) {
            return Response::badRequest('links/show.phtml', [
                'link' => $link,
                'can_update' => $can_update,
                'content' => $content,
                'share_on_mastodon' => $share_on_mastodon,
                'mastodon_configured' => $mastodon_configured,
                'errors' => $note->errors(),
            ]);
        }

        $note->save();

        services\LinkTags::refresh($link);

        if ($mastodon_configured && $share_on_mastodon) {
            $share_on_mastodon_job = new jobs\ShareOnMastodon();
            $share_on_mastodon_job->performAsap($user->id, $link->id, $note->id);
        }

        return Response::redirect('link', ['id' => $link_id]);
    }
}
