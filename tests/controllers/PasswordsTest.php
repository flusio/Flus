<?php

namespace flusio\controllers;

use flusio\auth;
use flusio\models;
use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class PasswordsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\MailerAsserts;

    public function testForgotRendersCorrectly()
    {
        $response = $this->appRun('GET', '/password/forgot');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'Reset your password');
    }

    public function testForgotRendersInfoIfFlashEmailSentIsTrue()
    {
        \Minz\Flash::set('email_sent', true);

        $response = $this->appRun('GET', '/password/forgot');

        $this->assertResponseContains($response, 'We’ve sent you an email to reset your password.');
    }

    public function testForgotRedirectsIfConnected()
    {
        $this->login();

        $response = $this->appRun('GET', '/password/forgot');

        $this->assertResponseCode($response, 302, '/');
    }

    public function testForgotRedirectsIfDemoIsEnabled()
    {
        \Minz\Configuration::$application['demo'] = true;

        $response = $this->appRun('GET', '/password/forgot');

        \Minz\Configuration::$application['demo'] = false;
        $this->assertResponseCode($response, 302, '/');
    }

    public function testResetRedirectsCorrectly()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 302, '/password/forgot');
    }

    public function testResetGeneratesAToken()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $user = $user->reload();
        $this->assertNotNull($user->reset_token);
        $token = models\Token::findBy(['token' => $user->reset_token]);
        $this->assertEquals(\Minz\Time::fromNow(1, 'hour'), $token->expired_at);
    }

    public function testResetSendsAnEmail()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertEmailsCount(1);
        $email_sent = \Minz\Tests\Mailer::take();
        $user = $user->reload();
        $this->assertEmailSubject($email_sent, '[flusio] Reset your password');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $user->reset_token);
    }

    public function testResetSetsFlashEmailSent()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertTrue(\Minz\Flash::get('email_sent'));
    }

    public function testResetRedirectsIfConnected()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = $this->fake('email');
        $user = $this->login([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $user = $user->reload();
        $this->assertNull($user->reset_token);
    }

    public function testResetRedirectsIfDemoIsEnabled()
    {
        \Minz\Configuration::$application['demo'] = true;
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        \Minz\Configuration::$application['demo'] = false;
        $this->assertResponseCode($response, 302, '/');
        $user = $user->reload();
        $this->assertNull($user->reset_token);
    }

    public function testResetFailsIfEmailIsEmpty()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = '';
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'The address email is invalid');
        $user = $user->reload();
        $this->assertNull($user->reset_token);
    }

    public function testResetFailsIfEmailDoesNotMatchUserEmail()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = $this->fakeUnique('email');
        $user_email = $this->fakeUnique('email');
        $user = UserFactory::create([
            'email' => $user_email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'We can’t find any account with this email address');
        $user = $user->reload();
        $this->assertNull($user->reset_token);
    }

    public function testResetFailsIfCsrfIsInvalid()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = \Minz\Csrf::generate();
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => 'not the token',
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $user = $user->reload();
        $this->assertNull($user->reset_token);
    }

    public function testEditRendersCorrectly()
    {
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/password/edit', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, "You’re changing the password of {$email}");
    }

    public function testEditFailsIfTokenIsNotPassed()
    {
        $email = $this->fake('email');
        UserFactory::create([
            'email' => $email,
        ]);

        $response = $this->appRun('GET', '/password/edit');

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
    }

    public function testEditFailsIfTokenIsInvalid()
    {
        $email = $this->fake('email');
        UserFactory::create([
            'email' => $email,
        ]);

        $response = $this->appRun('GET', '/password/edit', [
            't' => 'a fake token',
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
    }

    public function testEditFailsIfTokenIsNotAttachedToUser()
    {
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        UserFactory::create([
            'email' => $email,
        ]);

        $response = $this->appRun('GET', '/password/edit', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
    }

    public function testEditFailsIfTokenHasExpired()
    {
        $minutes = $this->fake('numberBetween', 0, 9000);
        $expired_at = \Minz\Time::ago($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/password/edit', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token has expired');
    }

    public function testEditFailsIfTokenIsInvalidated()
    {
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $invalidated_at = $this->fake('dateTime');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => $invalidated_at,
        ]);
        $email = $this->fake('email');
        UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
        ]);

        $response = $this->appRun('GET', '/password/edit', [
            't' => $token->token,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token has expired');
    }

    public function testUpdateChangesPasswordAndRedirectsCorrectly()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            't' => $token->token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($new_password));
    }

    public function testUpdateDeletesResetToken()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            't' => $token->token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $user = $user->reload();
        $this->assertNull($user->reset_token);
        $this->assertFalse(models\Token::exists($token->token));
    }

    public function testUpdateResetsExistingSessionsAndLogsIn()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);
        $session = SessionFactory::create([
            'user_id' => $user->id,
        ]);

        $user = auth\CurrentUser::get();
        $this->assertNull($user);
        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            't' => $token->token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $user = auth\CurrentUser::get();
        $this->assertNotNull($user);
        $this->assertSame($email, $user->email);
        $this->assertSame(1, models\Session::count());
        $new_session = models\Session::take();
        $this->assertNotSame($session->id, $new_session->id);
        $this->assertSame($user->id, $new_session->user_id);
    }

    public function testUpdateFailsIfTokenIsNotPassed()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfTokenIsInvalid()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            't' => 'a fake token',
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfTokenIsNotAttachedToUser()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            't' => $token->token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfTokenHasExpired()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 0, 9000);
        $expired_at = \Minz\Time::ago($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            't' => $token->token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token has expired');
        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfTokenIsInvalidated()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $invalidated_at = $this->fake('dateTime');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => $invalidated_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            't' => $token->token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token has expired');
        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfPasswordIsEmpty()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = '';
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => $csrf,
            't' => $token->token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The password is required');
        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $csrf = \Minz\Csrf::generate();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => $token->token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/password/edit', [
            'csrf' => 'not the token',
            't' => $token->token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }
}
