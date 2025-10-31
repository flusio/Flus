<?php

namespace App\controllers\my;

use App\forms;
use App\models;
use tests\factories\UserFactory;
use tests\factories\TokenFactory;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\MailerAsserts;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;

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

    public function testShowRendersCorrectlyWhenNotValidated(): void
    {
        $this->login([
            'validated_at' => null,
        ]);

        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Didn’t receive the email? Resend it');
    }

    public function testShowRendersCorrectlyWhenValidated(): void
    {
        $this->login([
            'validated_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Your account is now validated.');
    }

    public function testShowRendersCorrectlyWithValidationEmailSentStatus(): void
    {
        /** @var string */
        $email = $this->fake('email');
        $this->login([
            'email' => $email,
            'validated_at' => null,
        ]);
        \Minz\Flash::set('status', 'validation_email_sent');

        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, "We’ve just sent you an email at {$email}");
    }

    public function testShowRedirectsIfTheUserIsNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/account/validation');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fvalidation');
    }

    public function testNewRendersCorrectly(): void
    {
        $response = $this->appRun('GET', '/my/account/validation/new', [
            't' => 'some-token',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'You’re about to validate your account on Flus.');
    }

    public function testCreateValidatesAccountAndRedirects(): void
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

        $response = $this->appRun('POST', '/my/account/validation/new', [
            'csrf_token' => $this->csrfToken(forms\AccountValidation::class),
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 302, '/my/account/validation');
        $user = $user->reload();
        $this->assertEquals(\Minz\Time::now(), $user->validated_at);
        $this->assertNull($user->validation_token);
        $this->assertFalse(models\Token::exists($token->token));
    }

    public function testCreateSetsSubscriptionAccountId(): void
    {
        $subscriptions_host = \App\Configuration::$application['subscriptions_host'];
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

        $response = $this->appRun('POST', '/my/account/validation/new', [
            'csrf_token' => $this->csrfToken(forms\AccountValidation::class),
            't' => $token->token,
        ]);

        $user = $user->reload();
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testCreateFailsIfTokenHasExpired(): void
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

        $response = $this->appRun('POST', '/my/account/validation/new', [
            'csrf_token' => $this->csrfToken(forms\AccountValidation::class),
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The token has expired or has been invalidated');
        $user = $user->reload();
        $this->assertNull($user->validated_at);
    }

    public function testCreateFailsIfTokenHasBeenInvalidated(): void
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

        $response = $this->appRun('POST', '/my/account/validation/new', [
            'csrf_token' => $this->csrfToken(forms\AccountValidation::class),
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The token has expired or has been invalidated');
        $user = $user->reload();
        $this->assertNull($user->validated_at);
    }

    public function testCreateFailsIfTokenIsNotAssociatedToAUser(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);

        $response = $this->appRun('POST', '/my/account/validation/new', [
            'csrf_token' => $this->csrfToken(forms\AccountValidation::class),
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The token doesn’t exist');
    }

    public function testCreateFailsIfTokenDoesNotExist(): void
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

        $response = $this->appRun('POST', '/my/account/validation/new', [
            'csrf_token' => $this->csrfToken(forms\AccountValidation::class),
            't' => 'not a token',
        ]);

        $this->assertResponseCode($response, 400);
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
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/my/account/validation');
        $this->assertSame('validation_email_sent', \Minz\Flash::get('status'));
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertNotNull($email_sent);
        $this->assertEmailSubject($email_sent, '[Flus] Confirm your account');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token->token);
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
            'csrf' => \App\Csrf::generate(),
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
            'csrf' => \App\Csrf::generate(),
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
            'csrf' => \App\Csrf::generate(),
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
            'csrf' => \App\Csrf::generate(),
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

        $this->assertResponseCode($response, 302, '/my/account/validation');
        $this->assertSame(
            'A security verification failed: you should retry to submit the form.',
            \Minz\Flash::get('error'),
        );
        $this->assertEmailsCount(0);
    }

    public function testResendEmailFailsIfUserNotConnected(): void
    {
        $response = $this->appRun('POST', '/my/account/validation/email', [
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fvalidation');
        $this->assertEmailsCount(0);
    }
}
