<?php

namespace flusio\jobs\scheduled;

use flusio\jobs;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * Job to synchronize the subscriptions with the host
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SubscriptionsSync extends jobs\Job
{
    /**
     * Install the job in database.
     */
    public static function install()
    {
        $job_dao = new \flusio\models\dao\Job();
        $subscriptions_sync_job = new SubscriptionsSync();

        if (!$job_dao->findBy(['name' => $subscriptions_sync_job->name])) {
            $subscriptions_sync_job->performLater();
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+4 hours';
    }

    public function perform()
    {
        $app_conf = \Minz\Configuration::$application;
        if (!$app_conf['subscriptions_enabled']) {
            return;
        }

        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );

        // First, make sure all users have a subscription account. It might
        // happen for users who didn't validate their account yet, or if a
        // previous request failed.
        $users = models\User::listBy(['subscription_account_id' => null]);
        foreach ($users as $user) {
            $account = $subscriptions_service->account($user->email);
            if ($account) {
                $user->subscription_account_id = $account['id'];
                $user->subscription_expired_at = $account['expired_at'];
                $user->save();
            }
        }

        // Then, synchronize expiration dates.
        $users = models\User::listAll();
        $account_ids_to_users = array_column($users, null, 'subscription_account_id');
        $account_ids = array_keys($account_ids_to_users);

        $result = $subscriptions_service->sync($account_ids);
        if ($result === null) {
            return;
        }

        foreach ($result as $account_id => $expired_at) {
            if (!isset($account_ids_to_users[$account_id])) {
                \Minz\Log::error("Subscription account {$account_id} does not exist.");
                continue;
            }

            $user = $account_ids_to_users[$account_id];
            if ($user->subscription_expired_at != $expired_at) {
                $user->subscription_expired_at = $expired_at;
                $user->save();
            }
        }

        // Finally, reset the account ids if they were not included in the
        // response. A new account id will be fetched next time.
        $unknown_account_ids = array_diff($account_ids, array_keys($result));
        foreach ($unknown_account_ids as $account_id) {
            $user = $account_ids_to_users[$account_id];
            $user->subscription_account_id = null;
            $user->save();
        }
    }
}
