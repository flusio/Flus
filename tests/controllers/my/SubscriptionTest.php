<?php

namespace App\controllers\my;

use App\forms;
use tests\factories\UserFactory;

class SubscriptionTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\HttpHelper;
    use \tests\LoginHelper;

    #[\PHPUnit\Framework\Attributes\Before]
    public function initializeSubscriptionConfiguration(): void
    {
        \App\Configuration::$application['subscriptions_enabled'] = true;
    }

    #[\PHPUnit\Framework\Attributes\AfterClass]
    public static function resetSubscriptionConfiguration(): void
    {
        \App\Configuration::$application['subscriptions_enabled'] = false;
    }

    public function testCreateSetsSubscriptionProperties(): void
    {
        $subscriptions_host = \App\Configuration::$application['subscriptions_host'];
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $account_id = $this->fake('uuid');
        /** @var \DateTimeImmutable */
        $expired_at = $this->fake('dateTime');
        $subscription_api_url = "{$subscriptions_host}/api/account?email={$email}";
        $this->mockHttpWithResponse($subscription_api_url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "id": "{$account_id}",
                "expired_at": "{$expired_at->format(\Minz\Database\Column::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user = $this->login([
            'email' => $email,
            'subscription_account_id' => null,
            'subscription_expired_at' => \Minz\Time::now(),
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/account/subscription', [
            'csrf_token' => $this->csrfToken(forms\users\InitSubscription::class),
        ]);

        $this->assertResponseCode($response, 302, '/my/account');
        $user = $user->reload();
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testCreateDoesNothingIfUserAlreadyHasAccountId(): void
    {
        /** @var string */
        $account_id = $this->fake('regexify', '\w{32}');
        $expired_at = new \DateTimeImmutable('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => $account_id,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/account/subscription', [
            'csrf_token' => $this->csrfToken(forms\users\InitSubscription::class),
        ]);

        $this->assertResponseCode($response, 302, '/my/account');
        $user = $user->reload();
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateRedirectsIfUserIsNotConnected(): void
    {
        $expired_at = new \DateTimeImmutable('1970-01-01');
        $user = UserFactory::create([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/account/subscription', [
            'csrf_token' => $this->csrfToken(forms\users\InitSubscription::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fsubscription');
        $user = $user->reload();
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfUserIsNotValidated(): void
    {
        $expired_at = new \DateTimeImmutable('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at,
            'validated_at' => null,
            'created_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/account/subscription', [
            'csrf_token' => $this->csrfToken(forms\users\InitSubscription::class),
        ]);

        $this->assertResponseCode($response, 302, '/my/account');
        $this->assertSame(
            'You must verify your account first.',
            \Minz\Flash::get('error')
        );
        $user = $user->reload();
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $expired_at = new \DateTimeImmutable('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/account/subscription', [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/my/account');
        $this->assertSame(
            'A security verification failed: you should retry to submit the form.',
            \Minz\Flash::get('error')
        );
        $user = $user->reload();
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfSecretKeyIsInvalid(): void
    {
        $old_private_key = \App\Configuration::$application['subscriptions_private_key'];
        \App\Configuration::$application['subscriptions_private_key'] = 'not the key';
        $expired_at = new \DateTimeImmutable('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/account/subscription', [
            'csrf_token' => $this->csrfToken(forms\users\InitSubscription::class),
        ]);

        \App\Configuration::$application['subscriptions_private_key'] = $old_private_key;

        $this->assertResponseCode($response, 302, '/my/account');
        $this->assertSame(
            'An error occured when getting you a subscription account, please contact the support.',
            \Minz\Flash::get('error')
        );
        $user = $user->reload();
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testCreateFailsIfSubscriptionsAreDisabled(): void
    {
        \App\Configuration::$application['subscriptions_enabled'] = false;
        $expired_at = new \DateTimeImmutable('1970-01-01');
        $user = $this->login([
            'subscription_account_id' => null,
            'subscription_expired_at' => $expired_at,
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/account/subscription', [
            'csrf_token' => $this->csrfToken(forms\users\InitSubscription::class),
        ]);

        $user = $user->reload();
        $this->assertNull($user->subscription_account_id);
        $this->assertSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testRedirectRedirectsToLoginUrl(): void
    {
        $subscriptions_host = \App\Configuration::$application['subscriptions_host'];
        /** @var string */
        $account_id = $this->fake('uuid');
        /** @var string */
        $redirection_url = $this->fake('url');
        $subscription_api_url = "{$subscriptions_host}/api/account/login-url?account_id={$account_id}&service=Flus";
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

        $response = $this->appRun('GET', '/my/account/subscription');

        $this->assertResponseCode($response, 302, $redirection_url);
    }

    public function testRedirectRedirectsIfNotConnected(): void
    {
        UserFactory::create([
            // We don't make additional call for a failing test, but this id
            // should theorically be created on the subscriptions host first.
            'subscription_account_id' => 'some real id',
        ]);

        $response = $this->appRun('GET', '/my/account/subscription');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fsubscription');
    }

    public function testRedirectFailsIfUserHasNoAccountId(): void
    {
        $this->login([
            'subscription_account_id' => null,
        ]);

        $response = $this->appRun('GET', '/my/account/subscription');

        $this->assertResponseCode($response, 400);
    }

    public function testRedirectFailsIfUserHasInvalidAccountId(): void
    {
        $this->login([
            'subscription_account_id' => 'not an id',
        ]);

        $response = $this->appRun('GET', '/my/account/subscription');

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, 'please contact the support');
    }

    public function testRedirectFailsIfSubscriptionsAreDisabled(): void
    {
        \App\Configuration::$application['subscriptions_enabled'] = false;
        $this->login([
            // We don't make additional call for a failing test, but this id
            // should theorically be created on the subscriptions host first.
            'subscription_account_id' => 'some real id',
        ]);

        $response = $this->appRun('GET', '/my/account/subscription');

        $this->assertResponseCode($response, 404);
    }
}
