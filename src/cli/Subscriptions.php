<?php

namespace flusio\cli;

use Minz\Response;
use flusio\models;
use flusio\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscriptions
{
    /**
     * Synchronize the overdue subscriptions (or nearly overdue). It should be
     * used via a cron job.
     *
     * @response 400
     *     If the subscriptions are disabled
     * @response 200
     *
     * @return \Minz\Response
     */
    public function sync($request)
    {
        $app_conf = \Minz\Configuration::$application;
        if (!$app_conf['subscriptions_enabled']) {
            return Response::text(400, 'The subscriptions are disabled.');
        }

        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );

        $sync_results = [];

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
                $sync_results[] = "{$user->id}: ✅ subscription account created";
            } else {
                $sync_results[] = "{$user->id}: ❌ can't create subscription account";
            }
        }

        // Then, synchronize expiration date for users having account expiring
        // in 2 weeks or less.
        $before_this_date = \Minz\Time::fromNow(2, 'weeks');
        $users = models\User::daoToList('listBySubscriptionExpiredAtBefore', $before_this_date);
        foreach ($users as $user) {
            $expired_at = $subscriptions_service->expiredAt($user->subscription_account_id);
            if (!$expired_at) {
                $sync_results[] = "{$user->id}: ❌ can't get the expiration date";
            } elseif ($user->subscription_expired_at->getTimestamp() !== $expired_at->getTimestamp()) {
                $user->subscription_expired_at = $expired_at;
                $user->save();
                $sync_results[] = "{$user->id}: ✅ synchronized";
            }
        }

        if ($sync_results) {
            return Response::text(200, implode("\n", $sync_results));
        } else {
            return Response::text(200, '');
        }
    }
}
