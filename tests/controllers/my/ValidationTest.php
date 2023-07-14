<?php

namespace flusio\controllers\my;

use flusio\models;
use flusio\utils;
use tests\factories\UserFactory;
use tests\factories\TokenFactory;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\MailerAsserts;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;

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

    public function testShowWithoutTokenAndConnectedRendersCorrectly()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $this->login([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Didn’t receive the email? Resend it');
    }

    public function testShowWithoutTokenAndNotConnectedRedirectsToLogin()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        UserFactory::create([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fvalidation');
    }

    public function testShowWithValidationEmailSentStatusRendersCorrectly()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $this->login([
            'email' => $email,
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);
        \Minz\Flash::set('status', 'validation_email_sent');

        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "We’ve just sent you an email at {$email}");
    }

    public function testShowRedirectsIfUserConnectedAndRegistrationAlreadyValidated()
    {
        $this->login([
            'validated_at' => $this->fake('dateTime'),
        ]);

        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 302, '/');
    }

    public function testShowWithTokenRendersCorrectlyAndValidatesRegistration()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Your account is now validated');
        $user = $user->reload();
        $this->assertEquals(\Minz\Time::now(), $user->validated_at);
    }

    public function testShowWithTokenSetsSubscriptionAccountId()
    {
        $subscriptions_host = \Minz\Configuration::$application['subscriptions_host'];
        $this->freeze($this->fake('dateTime'));
        $email = $this->fake('email');
        $account_id = $this->fake('uuid');
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
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
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'email' => $email,
            'validated_at' => null,
            'validation_token' => $token->token,
            'subscription_account_id' => null,
            'subscription_expired_at' => $this->fake('dateTime'),
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $user = $user->reload();
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testShowWithTokenRedirectsIfRegistrationAlreadyValidated()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'validated_at' => $this->fake('dateTime'),
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 302, '/');
    }

    public function testShowWithTokenDeletesToken()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $this->assertFalse(models\Token::exists($token->token));
        $user = $user->reload();
        $this->assertNull($user->validation_token);
    }

    public function testShowWithTokenFailsIfTokenHasExpired()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::ago($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The token has expired or has been invalidated');
        $user = $user->reload();
        $this->assertNull($user->validated_at);
    }

    public function testShowWithTokenFailsIfTokenHasBeenInvalidated()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => $this->fake('dateTime'),
        ]);
        $user = UserFactory::create([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The token has expired or has been invalidated');
        $user = $user->reload();
        $this->assertNull($user->validated_at);
    }

    public function testShowFailsIfTokenIsNotAssociatedToAUser()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseContains($response, 'The token doesn’t exist');
    }

    public function testShowWithTokenFailsIfTokenDoesNotExist()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => 'not the token',
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseContains($response, 'The token doesn’t exist');
        $user = $user->reload();
        $this->assertNull($user->validated_at);
    }

    public function testResendEmailSendsAnEmailAndRedirects()
    {
        $email = $this->fake('email');
        $minutes = $this->fake('numberBetween', 31, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = $this->login([
            'email' => $email,
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $this->assertEmailsCount(0);

        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('validation_email_sent', \Minz\Flash::get('status'));
        $this->assertEmailsCount(1);
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertEmailSubject($email_sent, '[flusio] Confirm your account');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token->token);
    }

    public function testResendEmailRedirectsToRedictTo()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 31, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = $this->login([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => $user->csrf,
            'from' => '/about',
        ]);

        $this->assertResponseCode($response, 302, '/about');
        $this->assertSame('validation_email_sent', \Minz\Flash::get('status'));
    }

    public function testResendEmailCreatesANewTokenIfNoToken()
    {
        $email = $this->fake('email');
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $user = $this->login([
            'email' => $email,
            'validated_at' => null,
            'validation_token' => null,
        ]);

        $number_tokens = models\Token::count();

        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertSame($number_tokens + 1, models\Token::count());
        $user = $user->reload();
        $this->assertNotNull($user->validation_token);
    }

    public function testResendEmailCreatesANewTokenIfExpiresSoon()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 0, 30), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = $this->login([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $number_tokens = models\Token::count();

        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertSame($number_tokens + 1, models\Token::count());
        $user = $user->reload();
        $this->assertNotSame($user->validation_token, $token->token);
    }

    public function testResendEmailCreatesANewTokenIfInvalidated()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 31, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => $this->fake('dateTime')
        ]);
        $user = $this->login([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $number_tokens = models\Token::count();

        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertSame($number_tokens + 1, models\Token::count());
        $user = $user->reload();
        $this->assertNotSame($user->validation_token, $token->token);
    }

    public function testResendEmailRedirectsSilentlyIfAlreadyValidated()
    {
        $user = $this->login([
            'validated_at' => $this->fake('dateTime'),
        ]);

        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertEmailsCount(0);
    }

    public function testResendEmailFailsIfCsrfIsInvalid()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $this->login([
            'validated_at' => null,
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(
            'A security verification failed: you should retry to submit the form.',
            \Minz\Flash::get('error'),
        );
        $this->assertEmailsCount(0);
    }

    public function testResendEmailFailsIfUserNotConnected()
    {
        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => \Minz\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertEmailsCount(0);
    }
}
