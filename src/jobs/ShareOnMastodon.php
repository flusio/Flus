<?php

namespace App\jobs;

use App\models;
use App\services;

/**
 * Job to share a link/note on Mastodon.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ShareOnMastodon extends \Minz\Job
{
    public function perform(string $user_id, string $link_id, ?string $note_id): void
    {
        $mastodon_account = models\MastodonAccount::findBy([
            'user_id' => $user_id,
        ]);

        if (!$mastodon_account) {
            \Minz\Log::error("[ShareOnMastodon] User {$user_id} does not have a Mastodon account");
            return;
        }

        $link = models\Link::find($link_id);

        if (!$link) {
            \Minz\Log::error("[ShareOnMastodon] Link {$link_id} does not exist (shared by user {$user_id})");
            return;
        }

        $note = null;

        if ($note_id) {
            $note = models\Note::find($note_id);

            if (!$note) {
                \Minz\Log::error("[ShareOnMastodon] Note {$note_id} does not exist (shared by user {$user_id})");
                return;
            }
        }

        $mastodon_server = $mastodon_account->server();
        $mastodon_service = new services\Mastodon($mastodon_server);
        $mastodon_service->postStatus($mastodon_account, $link, $note);
    }
}
