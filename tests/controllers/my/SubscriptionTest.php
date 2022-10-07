<?php

namespace flusio\controllers\my;

use flusio\models;
use flusio\services;

class SubscriptionTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
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

    public function testCreateSetsSubscriptionProperties()
    {
        $subscriptions_host = \Minz\Configuration::$application['subscriptions_host'];
        $email = $this->fake('email');
        $account_id = $this->fake('uuid');
        $expired_at = $this->fake('dateTime');
        $subscription_api_url = "{$subscriptions_host}/api/account?email={$email}";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "id": "{$account_id}",
                "expired_at": "{$expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user = $this->login([
            'email' => $email,
            'subscription_account_id' => null,
            'subscription_expired_at' => $this->fake('iso8601'),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/account/subscription', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/my/account');
        $user = models\User::find($user->id);
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testCreateDoesNothingIfUserAlreadyHasAccountId()
    {
        $account_id = $this->fake('regexify', '\w{32}');
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/account/subscription', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/my/account');
        $user = models\User::find($user->id);
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateRedirectsIfUserIsNotConnected()
    {
        $expired_at = new \DateTime('1970-01-01');
        $user_id = $this->create('user', [
            'csrf' => 'a token',
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/account/subscription', [
            'csrf' => 'a token',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount');
        $user = models\User::find($user_id);
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfUserIsNotValidated()
    {
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('post', '/my/account/subscription', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/my/account');
        $user = models\User::find($user->id);
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/account/subscription', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/my/account');
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $user = models\User::find($user->id);
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
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/account/subscription', [
            'csrf' => $user->csrf,
        ]);

        \Minz\Configuration::$application['subscriptions_private_key'] = $old_private_key;

        $this->assertResponseCode($response, 302, '/my/account');
        $this->assertFlash(
            'error',
            'An error occured when getting you a subscription account, please contact the support.'
        );
        $user = models\User::find($user->id);
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfSubscriptionsAreDisabled()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        $expired_at = new \DateTime('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/account/subscription', [
            'csrf' => $user->csrf,
        ]);

        $user = models\User::find($user->id);
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testRedirectRedirectsToLoginUrl()
    {
        $subscriptions_host = \Minz\Configuration::$application['subscriptions_host'];
        $account_id = $this->fake('uuid');
        $redirection_url = $this->fake('url');
        $subscription_api_url = "{$subscriptions_host}/api/account/login-url?account_id={$account_id}&service=flusio";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "url": "{$redirection_url}"
            }
            TEXT
        );
        $this->login([
            'subscription_account_id' => $account_id,
        ]);

        $response = $this->appRun('get', '/my/account/subscription');

        $this->assertResponseCode($response, 302, $redirection_url);
    }

    public function testRedirectRedirectsIfNotConnected()
    {
        $this->create('user', [
            // We don't make additional call for a failing test, but this id
            // should theorically be created on the subscriptions host first.
            'subscription_account_id' => 'some real id',
        ]);

        $response = $this->appRun('get', '/my/account/subscription');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount');
    }

    public function testRedirectFailsIfUserHasNoAccountId()
    {
        $this->login([
            'subscription_account_id' => null,
        ]);

        $response = $this->appRun('get', '/my/account/subscription');

        $this->assertResponseCode($response, 400);
    }

    public function testRedirectFailsIfUserHasInvalidAccountId()
    {
        $this->login([
            'subscription_account_id' => 'not an id',
        ]);

        $response = $this->appRun('get', '/my/account/subscription');

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, 'please contact the support');
    }

    public function testRedirectFailsIfSubscriptionsAreDisabled()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
        $this->login([
            // We don't make additional call for a failing test, but this id
            // should theorically be created on the subscriptions host first.
            'subscription_account_id' => 'some real id',
        ]);

        $response = $this->appRun('get', '/my/account/subscription');

        $this->assertResponseCode($response, 404);
    }
}
