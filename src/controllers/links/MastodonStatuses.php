<?php

namespace App\controllers\links;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\jobs;
use App\models;
use App\services;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MastodonStatuses extends BaseController
{
    /**
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot share the link on Mastodon.
     */
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'shareOnMastodon', $link);

        $mastodon_account = $user->mastodonAccount();
        $mastodon_status = $mastodon_account->buildMastodonStatus($link);

        $contents = [$mastodon_status->content];
        foreach ($link->notes() as $note) {
            $contents[] = $note->content;
        }

        $form = new forms\links\MastodonStatuses([
            'contents' => $contents,
        ]);

        return Response::ok('links/mastodon_statuses/new.phtml', [
            'link' => $link,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string[] contents
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
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot share the link on Mastodon.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'shareOnMastodon', $link);

        $form = new forms\links\MastodonStatuses();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/mastodon_statuses/new.phtml', [
                'link' => $link,
                'form' => $form,
            ]);
        }

        $mastodon_account = $user->mastodonAccount();
        $previous_status = null;

        foreach ($form->contents as $content) {
            $mastodon_status = $mastodon_account->buildMastodonStatus($link, $content);
            $mastodon_status->setReplyTo($previous_status);
            $mastodon_status->save();

            $previous_status = $mastodon_status;
        }

        $share_on_mastodon_job = new jobs\ShareOnMastodon();
        $share_on_mastodon_job->performAsap($link->id);

        return Response::ok('links/mastodon_statuses/ongoing.phtml', [
            'link' => $link,
        ]);
    }
}
