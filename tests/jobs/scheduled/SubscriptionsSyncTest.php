<?php

namespace flusio\jobs\scheduled;

use flusio\models;
use flusio\services;

class SubscriptionsSyncTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\FactoriesHelper;

    /**
     * @before
     */
    public function initializeSubscriptionConfiguration()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
    }

    /**
     * @afterClass
     */
    public static function resetSubscriptionConfiguration()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
    }

    public function testQueue()
    {
        $subscriptions_sync_job = new SubscriptionsSync();

        $this->assertSame('default', $subscriptions_sync_job->queue);
    }

    public function testSchedule()
    {
        $subscriptions_sync_job = new SubscriptionsSync();

        $this->assertSame('+4 hours', $subscriptions_sync_job->frequency);
    }

    public function testSyncUpdatesExpiredAtOfOverdueUsers()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $account_id = $this->fake('uuid');
        $old_expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $new_expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/account/expired-at?account_id={$account_id}";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "expired_at": "{$new_expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user_id = $this->create('user', [
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $old_expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertEquals($new_expired_at, $user->subscription_expired_at);
    }

    public function testSyncUpdatesExpiredAtOfNearlyOverdueUsers()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $account_id = $this->fake('uuid');
        $old_expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 10), 'days');
        $new_expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/account/expired-at?account_id={$account_id}";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "expired_at": "{$new_expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user_id = $this->create('user', [
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $old_expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertEquals($new_expired_at, $user->subscription_expired_at);
    }

    public function testSyncUpdatesAccountIdIfMissing()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $email = $this->fake('email');
        $account_id = $this->fake('uuid');
        $expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/account?email={$email}";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "id": "{$account_id}",
                "expired_at": "{$expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user_id = $this->create('user', [
            'email' => $email,
            'subscription_account_id' => null,
            'subscription_expired_at' => $this->fake('iso8601'),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testSyncIgnoresUsersWithFarExpiredAt()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $app_conf = \Minz\Configuration::$application;
        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );
        $account = $subscriptions_service->account($this->fake('email'));
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 15, 42), 'days');
        $user_id = $this->create('user', [
            'subscription_account_id' => $account['id'],
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testSyncFailsWithInvalidAccountId()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $user_id = $this->create('user', [
            'subscription_account_id' => 'not-an-id',
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testSyncFailsIfSubscriptionsAreDisabled()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $user_id = $this->create('user', [
            // We don't make additional call for a failing test, but this id
            // should theorically be created on the subscriptions host first.
            'subscription_account_id' => 'some real id',
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }
}
