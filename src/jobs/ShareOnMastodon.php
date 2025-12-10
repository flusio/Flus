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
    public function perform(string $link_id): void
    {
        $link = models\Link::require($link_id);

        // TODO write this method
        $mastodon_statuses = models\MastodonStatus::listNotPostedByLink($link);
        // TODO order by thread

        foreach ($mastodon_statuses as $mastodon_status) {
            $mastodon_account = $mastodon_status->account();
            $mastodon_server = $mastodon_account->server();
            $mastodon_service = new services\Mastodon($mastodon_server);

            $mastodon_service->postStatus($mastodon_status);
        }
    }
}
