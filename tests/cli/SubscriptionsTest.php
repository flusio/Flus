<?php

namespace flusio\cli;

use flusio\utils;

class SubscriptionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
        self::$application = new \flusio\cli\Application();
    }

    /**
     * @afterClass
     */
    public static function disableSubscriptions()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
    }

    public function testIndexListsSubscriptionAccountIds()
    {
        $subscription_account_id_1 = utils\Random::hex(32);
        $subscription_account_id_2 = utils\Random::hex(32);
        $this->create('user', [
            'subscription_account_id' => $subscription_account_id_1,
        ]);
        $this->create('user', [
            'subscription_account_id' => null,
        ]);
        $this->create('user', [
            'subscription_account_id' => $subscription_account_id_2,
        ]);

        $response = $this->appRun('cli', '/subscriptions');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, <<<TEXT
        {$subscription_account_id_1}
        {$subscription_account_id_2}
        TEXT);
    }
}
