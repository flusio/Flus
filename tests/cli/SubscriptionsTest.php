<?php

namespace flusio\cli;

use flusio\models;
use flusio\services;

class SubscriptionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

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

    public function testSyncUpdatesExpiredAtOfOverdueUsers()
    {
        $user_dao = new models\dao\User();
        $app_conf = \Minz\Configuration::$application;
        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );
        $account = $subscriptions_service->account($this->fake('email'));
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $user_id = $this->create('user', [
            'subscription_account_id' => $account['id'],
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('cli', '/subscriptions/sync');

        $this->assertResponse($response, 200, "{$user_id}: OK");
        $user = new models\User($user_dao->find($user_id));
        $this->assertNotSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testSyncUpdatesExpiredAtOfNearlyOverdueUsers()
    {
        $user_dao = new models\dao\User();
        $app_conf = \Minz\Configuration::$application;
        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );
        $account = $subscriptions_service->account($this->fake('email'));
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 10), 'days');
        $user_id = $this->create('user', [
            'subscription_account_id' => $account['id'],
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('cli', '/subscriptions/sync');

        $this->assertResponse($response, 200, "{$user_id}: OK");
        $user = new models\User($user_dao->find($user_id));
        $this->assertNotSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testSyncIgnoresUsersWithFarExpiredAt()
    {
        $user_dao = new models\dao\User();
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

        $response = $this->appRun('cli', '/subscriptions/sync');

        $this->assertResponse($response, 200);
        $user = new models\User($user_dao->find($user_id));
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testSyncIgnoresUserWithoutAccountId()
    {
        $user_dao = new models\dao\User();
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $user_id = $this->create('user', [
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('cli', '/subscriptions/sync');

        $this->assertResponse($response, 200);
        $user = new models\User($user_dao->find($user_id));
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testSyncFailsWithInvalidAccountId()
    {
        $user_dao = new models\dao\User();
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $user_id = $this->create('user', [
            'subscription_account_id' => 'not-an-id',
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('cli', '/subscriptions/sync');

        $this->assertResponse($response, 200, "{$user_id}: failed");
        $user = new models\User($user_dao->find($user_id));
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testSyncFailsIfSubscriptionsAreDisabled()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        $user_dao = new models\dao\User();
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $user_id = $this->create('user', [
            // We don't make additional call for a failing test, but this id
            // should theorically be created on the subscriptions host first.
            'subscription_account_id' => 'some real id',
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('cli', '/subscriptions/sync');

        $this->assertResponse($response, 400, 'The subscriptions are disabled.');
        $user = new models\User($user_dao->find($user_id));
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }
}