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

        $user_dao = new models\dao\User();
        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );

        $sync_results = [];

        $before_this_date = \Minz\Time::fromNow(2, 'weeks');
        $db_users = $user_dao->listBySubscriptionExpiredAtBefore($before_this_date);
        foreach ($db_users as $db_user) {
            $user = new models\User($db_user);
            if (!$user->subscription_account_id) {
                continue;
            }

            $expired_at = $subscriptions_service->expiredAt($user->subscription_account_id);
            if ($expired_at) {
                $expired_at = date_create_from_format(
                    \Minz\Model::DATETIME_FORMAT,
                    $expired_at,
                );
                if ($user->subscription_expired_at->getTimestamp() !== $expired_at->getTimestamp()) {
                    $user->subscription_expired_at = $expired_at;
                    $user_dao->save($user);
                    $sync_results[] = "{$user->id}: OK";
                }
            } else {
                $sync_results[] = "{$user->id}: failed";
            }
        }

        if ($sync_results) {
            return Response::text(200, implode("\n", $sync_results));
        } else {
            return Response::text(200, '');
        }
    }
}
