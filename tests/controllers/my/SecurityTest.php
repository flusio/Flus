<?php

namespace flusio\controllers\my;

use flusio\auth;
use flusio\models;
use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;

    public function testShowRendersCorrectlyIfPasswordIsConfirmed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 15);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login([], [], [
            'confirmed_password_at' => $confirmed_at,
        ]);

        $response = $this->appRun('GET', '/my/security');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'You can change your login details here');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
    }

    public function testShowRendersConfirmFormIfPasswordWasNeverConfirmed(): void
    {
        $this->login([], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('GET', '/my/security');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We need you to confirm your password');
        $this->assertResponsePointer($response, 'my/security/show_to_confirm.phtml');
    }

    public function testShowRendersConfirmFormIfPasswordIsNotConfirmed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 16, 9000);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login([], [], [
            'confirmed_password_at' => $confirmed_at,
        ]);

        $response = $this->appRun('GET', '/my/security');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We need you to confirm your password');
        $this->assertResponsePointer($response, 'my/security/show_to_confirm.phtml');
    }

    public function testShowRedirectsIfUserIsNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/security');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
    }

    public function testUpdateChangesEmailAndPasswordAndRedirects(): void
    {
        /** @var string */
        $old_email = $this->fake('email');
        /** @var string */
        $new_email = $this->fake('email');
        /** @var string */
        $old_password = $this->fake('password');
        /** @var string */
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $user = $user->reload();
        $this->assertSame($new_email, $user->email);
        $this->assertTrue($user->verifyPassword($new_password));
    }

    public function testUpdateDoesNotChangePasswordIfEmpty(): void
    {
        /** @var string */
        $old_email = $this->fake('email');
        /** @var string */
        $new_email = $this->fake('email');
        /** @var string */
        $old_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => '',
        ]);

        $user = $user->reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateDeletesResetTokenIfAny(): void
    {
        /** @var string */
        $old_email = $this->fakeUnique('email');
        /** @var string */
        $new_email = $this->fakeUnique('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
        $new_password = $this->fakeUnique('password');
        $token = TokenFactory::create();
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
            'reset_token' => $token->token,
        ], [], [
            'confirmed_password_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $user = $user->reload();
        $this->assertNull($user->reset_token);
        $this->assertFalse(models\Token::exists($token->token));
    }

    public function testUpdateDeletesExistingSessionsExceptCurrentOne(): void
    {
        /** @var string */
        $old_email = $this->fakeUnique('email');
        /** @var string */
        $new_email = $this->fakeUnique('email');
        /** @var string */
        $old_password = $this->fakeUnique('password');
        /** @var string */
        $new_password = $this->fakeUnique('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now(),
        ]);
        $current_session = auth\CurrentUser::session();
        $session = SessionFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertNotNull($current_session);
        $this->assertSame(2, models\Session::count());

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $user = auth\CurrentUser::reload();
        $this->assertSame(1, models\Session::count());
        $this->assertFalse(models\Session::exists($session->id));
        $session = models\Session::take();
        $this->assertNotNull($session);
        $this->assertSame($current_session->id, $session->id);
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        /** @var string */
        $old_email = $this->fake('email');
        /** @var string */
        $new_email = $this->fake('email');
        /** @var string */
        $old_password = $this->fake('password');
        /** @var string */
        $new_password = $this->fake('password');
        $user = UserFactory::create([
            'csrf' => 'a token',
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => 'a token',
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
        $current_user = auth\CurrentUser::reload();
        $this->assertNull($current_user);
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfPasswordIsNotConfirmed(): void
    {
        /** @var string */
        $old_email = $this->fake('email');
        /** @var string */
        $new_email = $this->fake('email');
        /** @var string */
        $old_password = $this->fake('password');
        /** @var string */
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You must confirm your password');
        $this->assertResponsePointer($response, 'my/security/show_to_confirm.phtml');
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        /** @var string */
        $old_email = $this->fake('email');
        /** @var string */
        $new_email = $this->fake('email');
        /** @var string */
        $old_password = $this->fake('password');
        /** @var string */
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => 'not the token',
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfAnotherAccountHasTheSameEmail(): void
    {
        /** @var string */
        $old_email = $this->fake('email');
        /** @var string */
        $new_email = $this->fake('email');
        /** @var string */
        $old_password = $this->fake('password');
        /** @var string */
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now(),
        ]);
        UserFactory::create([
            'email' => $new_email,
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'An account already exists with this email address');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfEmailIsInvalid(): void
    {
        /** @var string */
        $old_email = $this->fake('email');
        /** @var string */
        $new_email = $this->fake('word');
        /** @var string */
        $old_password = $this->fake('password');
        /** @var string */
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is invalid');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfEmailIsMissing(): void
    {
        /** @var string */
        $old_email = $this->fake('email');
        /** @var string */
        $old_password = $this->fake('password');
        /** @var string */
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now(),
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf' => $user->csrf,
            'email' => '',
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is required');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testConfirmPasswordSetsConfirmedPasswordAtAndRedirects(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('POST', '/my/security/confirm', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $session = auth\CurrentUser::session();
        $this->assertNotNull($session);
        $now = \Minz\Time::now();
        $this->assertEquals($now, $session->confirmed_password_at);
    }

    public function testConfirmPasswordRedirectsIfUserIsNotConnected(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'csrf' => 'a token',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/my/security/confirm', [
            'csrf' => 'a token',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
    }

    public function testConfirmPasswordFailsIfCsrfIsInvalid(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('POST', '/my/security/confirm', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $this->assertSame(
            'A security verification failed: you should retry to submit the form.',
            \Minz\Flash::get('error')
        );
        $session = auth\CurrentUser::session();
        $this->assertNotNull($session);
        $this->assertNull($session->confirmed_password_at);
    }

    public function testConfirmPasswordFailsIfPasswordIsInvalid(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('POST', '/my/security/confirm', [
            'csrf' => $user->csrf,
            'password' => 'not the password',
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $this->assertEquals([
            'password_hash' => 'The password is incorrect.',
        ], \Minz\Flash::get('errors'));
        $session = auth\CurrentUser::session();
        $this->assertNotNull($session);
        $this->assertNull($session->confirmed_password_at);
    }
}
