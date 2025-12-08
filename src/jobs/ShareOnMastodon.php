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
    public function perform(string $mastodon_status_id): void
    {
        $mastodon_status = models\MastodonStatus::require($mastodon_status_id);

        $mastodon_account = $mastodon_status->account();
        $mastodon_server = $mastodon_account->server();
        $mastodon_service = new services\Mastodon($mastodon_server);

        $mastodon_service->postStatus($mastodon_status);
    }
}
