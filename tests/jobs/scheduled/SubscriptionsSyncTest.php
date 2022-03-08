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

    public function testInstall()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();

        $this->assertSame(0, $job_dao->count());

        SubscriptionsSync::install();

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertSame(1, $job_dao->count());
    }

    public function testSyncUpdatesExpiredAt()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $account_id = $this->fake('uuid');
        $old_expired_at = $this->fake('dateTime');
        $new_expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/accounts/sync";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "{$account_id}": "{$new_expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
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

    public function testSyncGetsAccountIdIfMissing()
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
            'validated_at' => $this->fake('iso8601'),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testSyncHandlesIfAccountIdFailsBeingGet()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $email = $this->fake('email');
        $expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/account?email={$email}";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 400
            Content-type: application/json

            {"error": "can’t get an id"}
            TEXT
        );
        $user_id = $this->create('user', [
            'email' => $email,
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertNull($user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testSyncIgnoresInvalidExpiredAt()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $account_id = $this->fake('uuid');
        $old_expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/accounts/sync";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "{$account_id}": "not a datetime"
            }
            TEXT
        );
        $user_id = $this->create('user', [
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $old_expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertEquals($old_expired_at, $user->subscription_expired_at);
    }

    public function testSyncIgnoresUnexpectedAccountIds()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $account_id_1 = $this->fake('uuid');
        // this account id is unknown to our system but returned by the API, it
        // should just be ignored.
        $account_id_2 = $this->fake('uuid');
        $old_expired_at = $this->fake('dateTime');
        $new_expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/accounts/sync";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "{$account_id_1}": "{$new_expired_at->format(\Minz\Model::DATETIME_FORMAT)}",
                "{$account_id_2}": "{$new_expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user_id = $this->create('user', [
            'subscription_account_id' => $account_id_1,
            'subscription_expired_at' => $old_expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertEquals($new_expired_at, $user->subscription_expired_at);
    }

    public function testSyncDoesNotGetAccountIdIfNotValidated()
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
            'validated_at' => null,
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertNull($user->subscription_account_id);
        $this->assertNotEquals($expired_at, $user->subscription_expired_at);
    }

    public function testSyncDoesNothingIfHttpIsInError()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        $account_id = $this->fake('uuid');
        $old_expired_at = $this->fake('dateTime');
        $new_expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/accounts/sync";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 500
            Content-type: application/json

            {
                "{$account_id}": "{$new_expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user_id = $this->create('user', [
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $old_expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        $user = models\User::find($user_id);
        $this->assertEquals($old_expired_at, $user->subscription_expired_at);
    }

    public function testSyncDoesNothingIfSubscriptionsAreDisabled()
    {
        $subscriptions_sync_job = new SubscriptionsSync();
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        $subscriptions_sync_job = new SubscriptionsSync();
        $account_id = $this->fake('uuid');
        $old_expired_at = $this->fake('dateTime');
        $new_expired_at = $this->fake('dateTime');
        $subscription_api_url = "https://next.flus.io/api/accounts/sync";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "{$account_id}": "{$new_expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user_id = $this->create('user', [
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $old_expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $subscriptions_sync_job->perform();

        \Minz\Configuration::$application['subscriptions_enabled'] = true;

        $user = models\User::find($user_id);
        $this->assertEquals($old_expired_at, $user->subscription_expired_at);
    }
}
