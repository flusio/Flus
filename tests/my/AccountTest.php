<?php

namespace flusio\my;

use flusio\models;
use flusio\services;
use flusio\utils;

class AccountTest extends \PHPUnit\Framework\TestCase
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

    public function testShowRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'my/account/show.phtml');
    }

    public function testShowRendersIfSubscriptionIsNotOverdue()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('randomDigitNotNull'), 'weeks');
        $this->login([
            'subscription_account_id' => $this->fake('regexify', '\w{32}'),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account');

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

        $response = $this->appRun('get', '/my/account');

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

        $response = $this->appRun('get', '/my/account');

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

        $response = $this->appRun('get', '/my/account');

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

        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 200, 'Validate your account');
    }

    public function testShowSyncsExpiredAtIfOverdue()
    {
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

        $response = $this->appRun('get', '/my/account');

        $user = models\User::find($user->id);
        $this->assertNotSame(
            $expired_at->getTimestamp(),
            $user->subscription_expired_at->getTimestamp()
        );
    }

    public function testShowRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/account');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount');
    }

    public function testValidationWithoutTokenAndConnectedRendersCorrectly()
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

    public function testValidationWithoutTokenAndNotConnectedRedirectsToLogin()
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

    public function testValidationWithValidationEmailSentStatusRendersCorrectly()
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

    public function testValidationRedirectsIfUserConnectedAndRegistrationAlreadyValidated()
    {
        $this->login([
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', '/my/account/validation');

        $this->assertResponse($response, 302, '/');
    }

    public function testValidationWithTokenRendersCorrectlyAndValidatesRegistration()
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

    public function testValidationWithTokenSetsSubscriptionAccountId()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
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

        \Minz\Configuration::$application['subscriptions_enabled'] = false;

        $user = models\User::find($user_id);
        $this->assertNotNull($user->subscription_account_id);
    }

    public function testValidationWithTokenRedirectsIfRegistrationAlreadyValidated()
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

    public function testValidationWithTokenDeletesToken()
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

    public function testValidationWithTokenFailsIfTokenHasExpired()
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

    public function testValidationWithTokenFailsIfTokenHasBeenInvalidated()
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

    public function testValidationFailsIfTokenIsNotAssociatedToAUser()
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

    public function testValidationWithTokenFailsIfTokenDoesNotExist()
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

    public function testResendValidationEmailSendsAnEmailAndRedirects()
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

    public function testResendValidationEmailRedirectsToRedictTo()
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

    public function testResendValidationEmailCreatesANewTokenIfNoToken()
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

    public function testResendValidationEmailCreatesANewTokenIfExpiresSoon()
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

    public function testResendValidationEmailCreatesANewTokenIfInvalidated()
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

    public function testResendValidationEmailRedirectsSilentlyIfAlreadyValidated()
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

    public function testResendValidationEmailFailsIfCsrfIsInvalid()
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

    public function testResendValidationEmailFailsIfUserNotConnected()
    {
        $response = $this->appRun('post', '/my/account/validation/email', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2F');
        $this->assertEmailsCount(0);
    }

    public function testDeletionRendersCorrectly()
    {
        $this->login();

        $response = $this->appRun('get', '/my/account/deletion');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'my/account/deletion.phtml');
    }

    public function testDeletionRedirectsToLoginIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/account/deletion');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteRedirectsToLoginAndDeletesTheUser()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/login');
        $this->assertFlash('status', 'user_deleted');
        $this->assertFalse(models\User::exists($user->id));
        $this->assertNull(utils\CurrentUser::get());
    }

    public function testDeleteRedirectsToLoginIfUserIsNotConnected()
    {
        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fmy%2Faccount%2Fdeletion');
    }

    public function testDeleteDeletesSessionsAssociatedToTheUser()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertSame(0, models\Session::count());
    }

    public function testDeleteFailsIfPasswordIsIncorrect()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => 'not the password',
        ]);

        $this->assertResponse($response, 400, 'The password is incorrect.');
        $this->assertTrue(models\User::exists($user->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertTrue(models\User::exists($user->id));
    }

    public function testDeleteFailsIfTryingToDeleteDemoAccount()
    {
        \Minz\Configuration::$application['demo'] = true;

        $password = $this->fake('password');
        $user = $this->login([
            'email' => 'demo@flus.io',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/account/deletion', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        \Minz\Configuration::$application['demo'] = false;
        $this->assertResponse($response, 400, 'Sorry but you cannot delete the demo account 😉');
        $this->assertTrue(models\User::exists($user->id));
    }
}
