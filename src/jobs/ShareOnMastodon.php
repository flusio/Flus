<?php

namespace App\jobs;

use App\models;
use App\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ShareOnMastodon extends \Minz\Job
{
    /**
     * Post a Mastodon thread where the given id is the one of the first status.
     */
    public function perform(string $mastodon_status_id): void
    {
        $mastodon_status = models\MastodonStatus::require($mastodon_status_id);

        $mastodon_account = $mastodon_status->account();
        $mastodon_server = $mastodon_account->server();
        $mastodon_service = new services\Mastodon($mastodon_server);

        $mastodon_service->postThread($mastodon_status);
    }
}
