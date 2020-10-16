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
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
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
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
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
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 200, 'You have a <strong>free subscription</strong>');
    }

    public function testShowRendersIfUserHasNoSubscriptionAccountId()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 200, 'Create your payment account');
    }

    public function testShowRendersIfUserIsNotValidated()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 200, 'validate your account');
    }

    public function testShowSyncsExpiredAtIfOverdue()
    {
        $user_dao = new models\dao\User();
        $app_conf = \Minz\Configuration::$application;
        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );
        $account = $subscriptions_service->account($this->fake('email'));
        $expired_at = \Minz\Time::ago($this->fake('randomDigitNotNull'), 'weeks');
        $user = $this->login([
            'subscription_account_id' => $account['id'],
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $user = new models\User($user_dao->find($user->id));
        $this->assertNotSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testShowRedirectsIfNotConnected()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->create('user', [
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
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
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/subscription');

        $this->assertResponse($response, 404);
    }

    public function testCreateSetsSubscriptionProperties()
    {
        $user_dao = new models\dao\User();
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/subscription', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 200);
        $user = new models\User($user_dao->find($user->id));
        $this->assertNotNull($user->subscription_account_id);
        $this->assertNotSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateDoesNothingIfUserAlreadyHasAccountId()
    {
        $user_dao = new models\dao\User();
        $account_id = $this->fake('regexify', '\w{32}');
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/subscription', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 200);
        $user = new models\User($user_dao->find($user->id));
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateRedirectsIfUserIsNotConnected()
    {
        $user_dao = new models\dao\User();
        $expired_at = new \DateTime('1970-01-01');
        $user_id = $this->create('user', [
            'csrf' => 'a token',
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/subscription', [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Fsubscription');
        $user = new models\User($user_dao->find($user_id));
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfUserIsNotValidated()
    {
        $user_dao = new models\dao\User();
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('post', '/my/subscription', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 400);
        $user = new models\User($user_dao->find($user->id));
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user_dao = new models\dao\User();
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/subscription', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $user = new models\User($user_dao->find($user->id));
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfSecretKeyIsInvalid()
    {
        $old_private_key = \Minz\Configuration::$application['subscriptions_private_key'];
        \Minz\Configuration::$application['subscriptions_private_key'] = 'not the key';
        $user_dao = new models\dao\User();
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/subscription', [
            'csrf' => $user->csrf,
        ]);

        \Minz\Configuration::$application['subscriptions_private_key'] = $old_private_key;

        $this->assertResponse($response, 500, 'please contact the support');
        $user = new models\User($user_dao->find($user->id));
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfSubscriptionsAreDisabled()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        $user_dao = new models\dao\User();
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/subscription', [
            'csrf' => $user->csrf,
        ]);

        $user = new models\User($user_dao->find($user->id));
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testRenewingRedirectsToLoginUrl()
    {
        $app_conf = \Minz\Configuration::$application;
        $subscriptions_service = new services\Subscriptions(
            $app_conf['subscriptions_host'],
            $app_conf['subscriptions_private_key']
        );
        $account = $subscriptions_service->account($this->fake('email'));
        $this->login([
            'subscription_account_id' => $account['id'],
        ]);

        $response = $this->appRun('get', '/my/subscription/renew');

        $this->assertResponse($response, 302);
        $response_headers = $response->headers(true);
        $this->assertStringContainsString(
            $app_conf['subscriptions_host'],
            $response_headers['Location']
        );
    }

    public function testRenewingRedirectsIfNotConnected()
    {
        $this->create('user', [
            // We don't make additional call for a failing test, but this id
            // should theorically be created on the subscriptions host first.
            'subscription_account_id' => 'some real id',
        ]);

        $response = $this->appRun('get', '/my/subscription/renew');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Fsubscription');
    }

    public function testRenewingFailsIfUserHasNoAccountId()
    {
        $this->login([
            'subscription_account_id' => null,
        ]);

        $response = $this->appRun('get', '/my/subscription/renew');

        $this->assertResponse($response, 400);
    }

    public function testRenewingFailsIfUserHasInvalidAccountId()
    {
        $this->login([
            'subscription_account_id' => 'not an id',
        ]);

        $response = $this->appRun('get', '/my/subscription/renew');

        $this->assertResponse($response, 500, 'please contact the support');
    }

    public function testRenewingFailsIfSubscriptionsAreDisabled()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        $this->login([
            // We don't make additional call for a failing test, but this id
            // should theorically be created on the subscriptions host first.
            'subscription_account_id' => 'some real id',
        ]);

        $response = $this->appRun('get', '/my/subscription/renew');

        $this->assertResponse($response, 404);
    }
}
