<?php

namespace flusio\controllers\my;

use flusio\models;
use flusio\utils;

class ValidationTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\MailerAsserts;

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
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/my/account/validation');

        $this->assertResponse($response, 200, 'Didn’t receive the email? Resend it');
    }

    public function testShowWithoutTokenAndNotConnectedRedirectsToLogin()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/my/account/validation');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fvalidation');
    }

    public function testShowWithValidationEmailSentStatusRendersCorrectly()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);
        utils\Flash::set('status', 'validation_email_sent');

        $response = $this->appRun('get', '/my/account/validation');

        $this->assertResponse($response, 200, 'We’ve just sent you an email!');
    }

    public function testShowRedirectsIfUserConnectedAndRegistrationAlreadyValidated()
    {
        $this->login([
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account/validation');

        $this->assertResponse($response, 302, '/');
    }

    public function testShowWithTokenRendersCorrectlyAndValidatesRegistration()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/my/account/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 200, 'Your account is now validated');
        $user = models\User::find($user_id);
        $this->assertEquals(\Minz\Time::now(), $user->validated_at);
    }

    public function testShowWithTokenSetsSubscriptionAccountId()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
            'subscription_account_id' => null,
        ]);

        $response = $this->appRun('get', '/my/account/validation', [
            't' => $token,
        ]);

        $user = models\User::find($user_id);
        $this->assertNotNull($user->subscription_account_id);
    }

    public function testShowWithTokenRedirectsIfRegistrationAlreadyValidated()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/my/account/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 302, '/');
    }

    public function testShowWithTokenDeletesToken()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token_id = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token_id,
        ]);

        $response = $this->appRun('get', '/my/account/validation', [
            't' => $token_id,
        ]);

        $token = models\Token::find($token_id);
        $user = models\User::find($user_id);
        $this->assertNull($token);
        $this->assertNull($user->validation_token);
    }

    public function testShowWithTokenFailsIfTokenHasExpired()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::ago($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/my/account/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 400, 'The token has expired or has been invalidated');
        $user = models\User::find($user_id);
        $this->assertNull($user->validated_at);
    }

    public function testShowWithTokenFailsIfTokenHasBeenInvalidated()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'invalidated_at' => $this->fake('iso8601'),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/my/account/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 400, 'The token has expired or has been invalidated');
        $user = models\User::find($user_id);
        $this->assertNull($user->validated_at);
    }

    public function testShowFailsIfTokenIsNotAssociatedToAUser()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('get', '/my/account/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 404, 'The token doesn’t exist');
    }

    public function testShowWithTokenFailsIfTokenDoesNotExist()
    {
        $this->freeze($this->fake('dateTime'));

        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/my/account/validation', [
            't' => 'not the token',
        ]);

        $this->assertResponse($response, 404, 'The token doesn’t exist');
        $user = models\User::find($user_id);
        $this->assertNull($user->validated_at);
    }

    public function testResendEmailSendsAnEmailAndRedirects()
    {
        $email = $this->fake('email');
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user = $this->login([
            'email' => $email,
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $this->assertEmailsCount(0);

        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertFlash('status', 'validation_email_sent');
        $this->assertEmailsCount(1);
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertEmailSubject($email_sent, '[flusio] Confirm your account');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token);
    }

    public function testResendEmailRedirectsToRedictTo()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 31, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user = $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => $user->csrf,
            'from' => '/about',
        ]);

        $this->assertResponse($response, 302, '/about');
        $this->assertFlash('status', 'validation_email_sent');
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

        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertSame($number_tokens + 1, models\Token::count());
        $user = models\User::find($user->id);
        $this->assertNotNull($user->validation_token);
    }

    public function testResendEmailCreatesANewTokenIfExpiresSoon()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 0, 30), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user = $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $number_tokens = models\Token::count();

        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertSame($number_tokens + 1, models\Token::count());
        $user = models\User::find($user->id);
        $this->assertNotSame($user->validation_token, $token);
    }

    public function testResendEmailCreatesANewTokenIfInvalidated()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 31, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'invalidated_at' => $this->fake('iso8601')
        ]);
        $user = $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $number_tokens = models\Token::count();

        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertSame($number_tokens + 1, models\Token::count());
        $user = models\User::find($user->id);
        $this->assertNotSame($user->validation_token, $token);
    }

    public function testResendEmailRedirectsSilentlyIfAlreadyValidated()
    {
        $user = $this->login([
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertEmailsCount(0);
    }

    public function testResendEmailFailsIfCsrfIsInvalid()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $this->assertEmailsCount(0);
    }

    public function testResendEmailFailsIfUserNotConnected()
    {
        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2F');
        $this->assertEmailsCount(0);
    }
}
