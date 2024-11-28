<?php

namespace App\controllers;

use App\auth;
use App\models;
use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class PasswordsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\MailerAsserts;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testForgotRendersCorrectly(): void
    {
        $response = $this->appRun('GET', '/password/forgot');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'passwords/forgot.phtml');
        $this->assertResponseContains($response, 'Reset your password');
    }

    public function testForgotRendersInfoIfFlashEmailSentIsTrue(): void
    {
        \Minz\Flash::set('email_sent', true);

        $response = $this->appRun('GET', '/password/forgot');

        $this->assertResponseContains($response, 'We’ve sent you an email to reset your password.');
    }

    public function testForgotRedirectsIfConnected(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/password/forgot');

        $this->assertResponseCode($response, 302, '/');
    }

    public function testForgotRedirectsIfDemoIsEnabled(): void
    {
        \App\Configuration::$application['demo'] = true;

        $response = $this->appRun('GET', '/password/forgot');

        \App\Configuration::$application['demo'] = false;
        $this->assertResponseCode($response, 302, '/');
    }

    public function testResetRedirectsCorrectly(): void
    {
        $this->freeze();
        $csrf = \Minz\Csrf::generate();
        /** @var string */
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

    public function testResetGeneratesAToken(): void
    {
        $this->freeze();
        $csrf = \Minz\Csrf::generate();
        /** @var string */
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
        $this->assertNotNull($token);
        $this->assertEquals(
            \Minz\Time::fromNow(1, 'hour')->getTimestamp(),
            $token->expired_at->getTimestamp(),
        );
    }

    public function testResetSendsAnEmail(): void
    {
        $this->freeze();
        $csrf = \Minz\Csrf::generate();
        /** @var string */
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
        $this->assertNotNull($email_sent);
        $user = $user->reload();
        $this->assertNotNull($user->reset_token);
        $this->assertEmailSubject($email_sent, '[Flus] Reset your password');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $user->reset_token);
    }

    public function testResetSetsFlashEmailSent(): void
    {
        $this->freeze();
        $csrf = \Minz\Csrf::generate();
        /** @var string */
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

    public function testResetRedirectsIfConnected(): void
    {
        $this->freeze();
        $csrf = \Minz\Csrf::generate();
        /** @var string */
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

    public function testResetRedirectsIfDemoIsEnabled(): void
    {
        \App\Configuration::$application['demo'] = true;
        $this->freeze();
        $csrf = \Minz\Csrf::generate();
        /** @var string */
        $email = $this->fake('email');
        $user = UserFactory::create([
            'email' => $email,
            'reset_token' => null,
        ]);

        $response = $this->appRun('POST', '/password/forgot', [
            'csrf' => $csrf,
            'email' => $email,
        ]);

        \App\Configuration::$application['demo'] = false;
        $this->assertResponseCode($response, 302, '/');
        $user = $user->reload();
        $this->assertNull($user->reset_token);
    }

    public function testResetFailsIfEmailIsEmpty(): void
    {
        $this->freeze();
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

    public function testResetFailsIfEmailDoesNotMatchUserEmail(): void
    {
        $this->freeze();
        $csrf = \Minz\Csrf::generate();
        /** @var string */
        $email = $this->fakeUnique('email');
        /** @var string */
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

    public function testResetFailsIfCsrfIsInvalid(): void
    {
        $this->freeze();
        $csrf = \Minz\Csrf::generate();
        /** @var string */
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

    public function testEditRendersCorrectly(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
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

    public function testEditFailsIfTokenIsNotPassed(): void
    {
        /** @var string */
        $email = $this->fake('email');
        UserFactory::create([
            'email' => $email,
        ]);

        $response = $this->appRun('GET', '/password/edit');

        $this->assertResponseCode($response, 404);
        $this->assertResponsePointer($response, 'passwords/edit.phtml');
        $this->assertResponseContains($response, 'The token doesn’t exist.');
    }

    public function testEditFailsIfTokenIsInvalid(): void
    {
        /** @var string */
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

    public function testEditFailsIfTokenIsNotAttachedToUser(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
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

    public function testEditFailsIfTokenHasExpired(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 9000);
        $expired_at = \Minz\Time::ago($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
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

    public function testEditFailsIfTokenIsInvalidated(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        /** @var \DateTimeImmutable */
        $invalidated_at = $this->fake('dateTime');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => $invalidated_at,
        ]);
        /** @var string */
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

    public function testUpdateChangesPasswordAndRedirectsCorrectly(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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

    public function testUpdateDeletesResetToken(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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

    public function testUpdateResetsExistingSessionsAndLogsIn(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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
        $this->assertNotNull($new_session);
        $this->assertNotSame($session->id, $new_session->id);
        $this->assertSame($user->id, $new_session->user_id);
    }

    public function testUpdateFailsIfTokenIsNotPassed(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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

    public function testUpdateFailsIfTokenIsInvalid(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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

    public function testUpdateFailsIfTokenIsNotAttachedToUser(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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

    public function testUpdateFailsIfTokenHasExpired(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 9000);
        $expired_at = \Minz\Time::ago($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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

    public function testUpdateFailsIfTokenIsInvalidated(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        /** @var \DateTimeImmutable */
        $invalidated_at = $this->fake('dateTime');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => $invalidated_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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

    public function testUpdateFailsIfPasswordIsEmpty(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
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

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $csrf = \Minz\Csrf::generate();
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
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
