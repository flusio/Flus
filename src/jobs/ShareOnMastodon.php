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
     *
     * If the account is not setup (e.g. its access token has been
     * invalidated), the job fails so it is retried later by the jobs watcher,
     * once the user has reconnected their account.
     *
     * @throws \RuntimeException
     *     If the account is not setup.
     * @throws services\MastodonError
     *     If the Mastodon host returns an error.
     */
    public function perform(string $mastodon_status_id): void
    {
        $mastodon_status = models\MastodonStatus::find($mastodon_status_id);

        if (!$mastodon_status) {
            // The status has probably been deleted by the Cleaner job.
            return;
        }

        $mastodon_account = $mastodon_status->account();

        if (!$mastodon_account->isSetup()) {
            throw new \RuntimeException(
                "MastodonAccount #{$mastodon_account->id} is not setup, the job will be retried later."
            );
        }

        $mastodon_server = $mastodon_account->server();
        $mastodon_service = new services\Mastodon($mastodon_server);

        try {
            $mastodon_service->postThread($mastodon_status);
        } catch (services\MastodonInvalidAccessTokenError $e) {
            $mastodon_account->invalidateAccessToken();
            $mastodon_account->save();

            throw $e;
        }
    }
}
