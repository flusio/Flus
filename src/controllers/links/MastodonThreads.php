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
class MastodonThreads extends BaseController
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

        $link = $user->obtainLink($link);
        if (!$link->isPersisted()) {
            $link->save();
        }

        $mastodon_account = $user->mastodonAccount();
        $mastodon_server = $mastodon_account->server();
        $mastodon_status = $mastodon_account->buildMastodonStatus($link);
        $options = $mastodon_account->options;

        $contents = [$mastodon_status->content];
        if ($options['prefill_with_notes']) {
            foreach ($link->notes() as $note) {
                $mastodon_status = $mastodon_account->buildMastodonStatus($link, $note->content);
                $contents[] = $mastodon_status->content;
            }
        }

        $form = new forms\links\MastodonThread([
            'contents' => $contents,
        ], options: [
            'default_content' => $options['post_scriptum_in_all_posts'] ? $options['post_scriptum'] : '',
            'max_chars' => $mastodon_server->statuses_max_characters,
        ]);

        return Response::ok('links/mastodon_threads/new.html.twig', [
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
     * @response 302 /links/:id/shares/mastodon/queued
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

        $link = $user->obtainLink($link);
        if (!$link->isPersisted()) {
            $link->save();
        }

        $mastodon_account = $user->mastodonAccount();
        $mastodon_server = $mastodon_account->server();
        $options = $mastodon_account->options;

        $form = new forms\links\MastodonThread(options: [
            'default_content' => $options['post_scriptum_in_all_posts'] ? $options['post_scriptum'] : '',
            'max_chars' => $mastodon_server->statuses_max_characters,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/mastodon_threads/new.html.twig', [
                'link' => $link,
                'form' => $form,
            ]);
        }

        $mastodon_account = $user->mastodonAccount();
        $first_status = null;
        $previous_status = null;

        foreach ($form->contents as $content) {
            if (!$content) {
                continue;
            }

            $mastodon_status = $mastodon_account->buildMastodonStatus($link);
            $mastodon_status->content = $content;
            $mastodon_status->setReplyTo($previous_status);
            $mastodon_status->save();

            $previous_status = $mastodon_status;

            if ($first_status === null) {
                $first_status = $mastodon_status;
            }
        }

        if ($first_status) {
            $share_on_mastodon_job = new jobs\ShareOnMastodon();
            $share_on_mastodon_job->performAsap($first_status->id);
        }

        return Response::redirect('link mastodon thread created', [
            'id' => $link->id,
        ]);
    }

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
    public function created(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'shareOnMastodon', $link);

        return Response::ok('links/mastodon_threads/created.html.twig', [
            'link' => $link,
        ]);
    }
}
