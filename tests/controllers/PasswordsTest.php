<?php

namespace flusio\controllers;

use flusio\auth;
use flusio\models;
use flusio\utils;

class PasswordsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\MailerAsserts;

    public function testForgotRendersCorrectly()
    {
        $response = $this->appRun('get', '/password/forgot');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'Reset your password');
    }

    public function testForgotRendersInfoIfFlashEmailSentIsTrue()
    {
        utils\Flash::set('email_sent', true);

        $response = $this->appRun('get', '/password/forgot');

        $this->assertResponseContains($response, 'We’ve sent you an email to reset your password.');
    }

    public function testForgotRedirectsIfConnected()
    {
        $this->login();

        $response = $this->appRun('get', '/password/forgot');

        $this->assertResponseCode($response, 302, '/');
    }

    public function testResetRedirectsCorrectly()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = (new \Minz\CSRF())->generateToken();
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('post', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 302, '/password/forgot');
    }

    public function testResetGeneratesAToken()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = (new \Minz\CSRF())->generateToken();
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('post', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $user = models\User::find($user_id);
        $this->assertNotNull($user->reset_token);
        $token = models\Token::findBy(['token' => $user->reset_token]);
        $this->assertEquals(\Minz\Time::fromNow(1, 'hour'), $token->expired_at);
    }

    public function testResetSendsAnEmail()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = (new \Minz\CSRF())->generateToken();
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('post', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertEmailsCount(1);
        $email_sent = \Minz\Tests\Mailer::take();
        $user = models\User::find($user_id);
        $this->assertEmailSubject($email_sent, '[flusio] Reset your password');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $user->reset_token);
    }

    public function testResetSetsFlashEmailSent()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = (new \Minz\CSRF())->generateToken();
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('post', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertFlash('email_sent', true);
    }

    public function testResetFailsIfEmailIsEmpty()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = (new \Minz\CSRF())->generateToken();
        $email = '';
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('post', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'The address email is invalid');
        $user = models\User::find($user_id);
        $this->assertNull($user->reset_token);
    }

    public function testResetFailsIfEmailDoesNotMatchUserEmail()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = (new \Minz\CSRF())->generateToken();
        $email = $this->fakeUnique('email');
        $user_email = $this->fakeUnique('email');
        $user_id = $this->create('user', [
            'email' => $user_email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('post', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'We can’t find any account with this email address');
        $user = models\User::find($user_id);
        $this->assertNull($user->reset_token);
    }

    public function testResetFailsIfCsrfIsInvalid()
    {
        $this->freeze($this->fake('dateTime'));
        $csrf = (new \Minz\CSRF())->generateToken();
        $email = $this->fake('email');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('post', '/password/forgot', [
            'csrf' => 'not the token',
            'email' => $email,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $user = models\User::find($user_id);
        $this->assertNull($user->reset_token);
    }

    public function testEditRendersCorrectly()
    {
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
        ]);

        $response = $this->appRun('get', '/password/edit', [
            't' => $token,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, "You’re changing the password of {$email}");
    }

    public function testEditFailsIfTokenIsNotPassed()
    {
        $email = $this->fake('email');
        $this->create('user', [
            'email' => $email,
        ]);

        $response = $this->appRun('get', '/password/edit');

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
    }

    public function testEditFailsIfTokenIsInvalid()
    {
        $token = 'a fake token';
        $email = $this->fake('email');
        $this->create('user', [
            'email' => $email,
        ]);

        $response = $this->appRun('get', '/password/edit', [
            't' => $token,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
    }

    public function testEditFailsIfTokenIsNotAttachedToUser()
    {
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $this->create('user', [
            'email' => $email,
        ]);

        $response = $this->appRun('get', '/password/edit', [
            't' => $token,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
    }

    public function testEditFailsIfTokenHasExpired()
    {
        $minutes = $this->fake('numberBetween', 0, 9000);
        $expired_at = \Minz\Time::ago($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
        ]);

        $response = $this->appRun('get', '/password/edit', [
            't' => $token,
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
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'invalidated_at' => $invalidated_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
        ]);

        $response = $this->appRun('get', '/password/edit', [
            't' => $token,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token has expired');
    }

    public function testUpdateChangesPasswordAndRedirectsCorrectly()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $user = models\User::find($user_id);
        $this->assertTrue($user->verifyPassword($new_password));
    }

    public function testUpdateDeletesResetToken()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $user = models\User::find($user_id);
        $this->assertNull($user->reset_token);
        $this->assertFalse(models\Token::exists($token));
    }

    public function testUpdateResetsExistingSessionsAndLogsIn()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);
        $session_id = $this->create('session', [
            'user_id' => $user_id,
        ]);

        $user = auth\CurrentUser::get();
        $this->assertNull($user);
        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $user = auth\CurrentUser::get();
        $this->assertNotNull($user);
        $this->assertSame($email, $user->email);
        $this->assertSame(1, models\Session::count());
        $session = models\Session::take();
        $this->assertNotSame($session_id, $session->id);
        $this->assertSame($user->id, $session->user_id);
    }

    public function testUpdateFailsIfTokenIsNotPassed()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
        $user = models\User::find($user_id);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfTokenIsInvalid()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = 'a fake token';
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
        $user = models\User::find($user_id);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfTokenIsNotAttachedToUser()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
        $user = models\User::find($user_id);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfTokenHasExpired()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 0, 9000);
        $expired_at = \Minz\Time::ago($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token has expired');
        $user = models\User::find($user_id);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfTokenIsInvalidated()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $invalidated_at = $this->fake('dateTime');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'invalidated_at' => $invalidated_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token has expired');
        $user = models\User::find($user_id);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfPasswordIsEmpty()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = '';
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => $csrf,
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The password is required');
        $user = models\User::find($user_id);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $csrf = (new \Minz\CSRF())->generateToken();
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $email = $this->fake('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'reset_token' => $token,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/password/edit', [
            'csrf' => 'not the token',
            't' => $token,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $user = models\User::find($user_id);
        $this->assertTrue($user->verifyPassword($old_password));
    }
}
