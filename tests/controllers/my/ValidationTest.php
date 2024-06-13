<?php

namespace App\controllers\my;

use App\models;
use App\utils;
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

    #[\PHPUnit\Framework\Attributes\Before]
    public function initializeSubscriptionConfiguration(): void
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
    }

    #[\PHPUnit\Framework\Attributes\AfterClass]
    public static function resetSubscriptionConfiguration(): void
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = false;
    }

    public function testShowWithoutTokenAndConnectedRendersCorrectly(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testShowWithoutTokenAndNotConnectedRedirectsToLogin(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testShowWithValidationEmailSentStatusRendersCorrectly(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
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

    public function testShowRedirectsIfUserConnectedAndRegistrationAlreadyValidated(): void
    {
        $this->login([
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 302, '/');
    }

    public function testShowWithTokenRendersCorrectlyAndValidatesRegistration(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testShowWithTokenSetsSubscriptionAccountId(): void
    {
        /** @var string */
        $subscriptions_host = \Minz\Configuration::$application['subscriptions_host'];
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $account_id = $this->fake('uuid');
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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
            'subscription_expired_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $user = $user->reload();
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testShowWithTokenRedirectsIfRegistrationAlreadyValidated(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'validated_at' => \Minz\Time::now(),
            'validation_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 302, '/');
    }

    public function testShowWithTokenDeletesToken(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testShowWithTokenFailsIfTokenHasExpired(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::ago($minutes, 'minutes');
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

    public function testShowWithTokenFailsIfTokenHasBeenInvalidated(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => \Minz\Time::now(),
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

    public function testShowFailsIfTokenIsNotAssociatedToAUser(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);

        $response = $this->appRun('GET', '/my/account/validation', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseContains($response, 'The token doesn’t exist');
    }

    public function testShowWithTokenFailsIfTokenDoesNotExist(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testResendEmailSendsAnEmailAndRedirects(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var int */
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
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertNotNull($email_sent);
        $this->assertEmailSubject($email_sent, '[Flus] Confirm your account');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token->token);
    }

    public function testResendEmailRedirectsToRedictTo(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 31, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testResendEmailCreatesANewTokenIfNoToken(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testResendEmailCreatesANewTokenIfExpiresSoon(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 30);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testResendEmailCreatesANewTokenIfInvalidated(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 31, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => \Minz\Time::now()
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

    public function testResendEmailRedirectsSilentlyIfAlreadyValidated(): void
    {
        $user = $this->login([
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertEmailsCount(0);
    }

    public function testResendEmailFailsIfCsrfIsInvalid(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
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

    public function testResendEmailFailsIfUserNotConnected(): void
    {
        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => \Minz\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertEmailsCount(0);
    }
}
