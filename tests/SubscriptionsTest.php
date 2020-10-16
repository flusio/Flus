<?php

namespace flusio;

class SubscriptionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

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

    public function testShowRendersIfSubscriptionIsNotOverdue()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 200, 'Your subscription will expire on');
    }

    public function testShowRendersIfSubscriptionIsOverdue()
    {
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 200, 'Your subscription expired on');
    }

    public function testShowRendersIfSubscriptionIsExempted()
    {
        $expired_at = new \DateTime('1970-01-01');
        $this->login([
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 200, 'You have a <strong>free subscription</strong>');
    }

    public function testShowRendersIfUserIsNotValidated()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 200, 'validate your account');
    }

    public function testShowRedirectsIfNotConnected()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->create('user', [
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Fsubscription');
    }

    public function testShowFailsIfSubscriptionsAreDisabled()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 404);
    }
}
