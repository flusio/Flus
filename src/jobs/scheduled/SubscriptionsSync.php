<?php

namespace App\jobs\scheduled;

use App\models;
use App\services;

/**
 * Job to synchronize the subscriptions with the host
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SubscriptionsSync extends \Minz\Job
{
    /**
     * Install the job in database.
     */
    public static function install(): void
    {
        $subscriptions_sync_job = new self();

        if (!\Minz\Job::existsBy(['name' => $subscriptions_sync_job->name])) {
            $subscriptions_sync_job->performAsap();
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+4 hours';
    }

    public function perform(): void
    {
        if (!\App\Configuration::areSubscriptionsEnabled()) {
            return;
        }

        $subscriptions_service = new services\Subscriptions();

        // First, make sure all users have a subscription account. It might
        // happen if a previous request failed.
        $users = models\User::listBy(['subscription_account_id' => null]);
        foreach ($users as $user) {
            if (!$user->isValidated()) {
                continue;
            }

            $subscriptions_service->initAccount($user);
            $user->save();
        }

        // Then, synchronize expiration dates.
        $users = models\User::listAll();
        $subscriptions_service->sync($users);
    }
}
