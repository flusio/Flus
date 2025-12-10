<?php

namespace App\jobs\scheduled;

use App\models;
use App\services;

/**
 * Job to synchronize the Mastodon servers statuses max characters.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MastodonServersSync extends \Minz\Job
{
    /**
     * Install the job in database.
     */
    public static function install(): void
    {
        $job = new self();

        if (!\Minz\Job::existsBy(['name' => $job->name])) {
            $perform_at = \Minz\Time::relative('tomorrow 2:00');
            $job->performLater($perform_at);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+1 day';
    }

    public function perform(): void
    {
        $servers = models\MastodonServer::listAll();
        foreach ($servers as $server) {
            $mastodon_service = new services\Mastodon($server);

            try {
                $max_characters = $mastodon_service->getServerMaxCharacters();
            } catch (services\MastodonError $e) {
                \Minz\Log::error("[MastodonServersSync] {$e->getMessage()}");
                $max_characters = 500;
            }

            $server->statuses_max_characters = $max_characters;
            $server->save();
        }
    }
}
