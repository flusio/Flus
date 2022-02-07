<?php

namespace flusio\controllers\my;

use flusio\auth;
use flusio\models;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;

    public function testShowRendersCorrectlyIfPasswordIsConfirmed()
    {
        $this->freeze($this->fake('dateTime'));
        $confirmed_at = \Minz\Time::ago($this->fake('numberBetween', 0, 15), 'minutes');
        $this->login([], [], [
            'confirmed_password_at' => $confirmed_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('get', '/my/security');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'You can change your login details here');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
    }

    public function testShowRendersConfirmFormIfPasswordWasNeverConfirmed()
    {
        $this->login([], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('get', '/my/security');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We need you to confirm your password');
        $this->assertResponsePointer($response, 'my/security/show_to_confirm.phtml');
    }

    public function testShowRendersConfirmFormIfPasswordIsNotConfirmed()
    {
        $this->freeze($this->fake('dateTime'));
        $confirmed_at = \Minz\Time::ago($this->fake('numberBetween', 16, 9000), 'minutes');
        $this->login([], [], [
            'confirmed_password_at' => $confirmed_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('get', '/my/security');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We need you to confirm your password');
        $this->assertResponsePointer($response, 'my/security/show_to_confirm.phtml');
    }

    public function testShowRedirectsIfUserIsNotConnected()
    {
        $response = $this->appRun('get', '/my/security');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
    }

    public function testUpdateChangesEmailAndPasswordAndRedirects()
    {
        $old_email = $this->fake('email');
        $new_email = $this->fake('email');
        $old_password = $this->fake('password');
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $user = auth\CurrentUser::reload();
        $this->assertSame($new_email, $user->email);
        $this->assertTrue($user->verifyPassword($new_password));
    }

    public function testUpdateDoesNotChangePasswordIfEmpty()
    {
        $old_email = $this->fake('email');
        $new_email = $this->fake('email');
        $old_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => '',
        ]);

        $user = auth\CurrentUser::reload();
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateDeletesResetTokenIfAny()
    {
        $old_email = $this->fakeUnique('email');
        $new_email = $this->fakeUnique('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $token = $this->create('token');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
            'reset_token' => $token,
        ], [], [
            'confirmed_password_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $user = auth\CurrentUser::reload();
        $this->assertNull($user->reset_token);
        $this->assertFalse(models\Token::exists($token));
    }

    public function testUpdateDeletesExistingSessionsExceptCurrentOne()
    {
        $old_email = $this->fakeUnique('email');
        $new_email = $this->fakeUnique('email');
        $old_password = $this->fakeUnique('password');
        $new_password = $this->fakeUnique('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $current_session = auth\CurrentUser::session();
        $session_id = $this->create('session', [
            'user_id' => $user->id,
        ]);

        $this->assertSame(2, models\Session::count());

        $response = $this->appRun('post', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $user = auth\CurrentUser::reload();
        $this->assertSame(1, models\Session::count());
        $this->assertFalse(models\Session::exists($session_id));
        $session = models\Session::take();
        $this->assertSame($current_session->id, $session->id);
    }

    public function testUpdateRedirectsIfNotConnected()
    {
        $old_email = $this->fake('email');
        $new_email = $this->fake('email');
        $old_password = $this->fake('password');
        $new_password = $this->fake('password');
        $user_id = $this->create('user', [
            'csrf' => 'a token',
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => 'a token',
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
        $user = auth\CurrentUser::reload();
        $this->assertNull($user);
        $user = models\User::find($user_id);
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfPasswordIsNotConfirmed()
    {
        $old_email = $this->fake('email');
        $new_email = $this->fake('email');
        $old_password = $this->fake('password');
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You must confirm your password');
        $this->assertResponsePointer($response, 'my/security/show_to_confirm.phtml');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $old_email = $this->fake('email');
        $new_email = $this->fake('email');
        $old_password = $this->fake('password');
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => 'not the token',
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfAnotherAccountHasTheSameEmail()
    {
        $old_email = $this->fake('email');
        $new_email = $this->fake('email');
        $old_password = $this->fake('password');
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->create('user', [
            'email' => $new_email,
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'An account already exists with this email address');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfEmailIsInvalid()
    {
        $old_email = $this->fake('email');
        $new_email = $this->fake('word');
        $old_password = $this->fake('password');
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => $user->csrf,
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is invalid');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfEmailIsMissing()
    {
        $old_email = $this->fake('email');
        $old_password = $this->fake('password');
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => password_hash($old_password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('post', '/my/security', [
            'csrf' => $user->csrf,
            'email' => '',
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is required');
        $this->assertResponsePointer($response, 'my/security/show_confirmed.phtml');
        $user = auth\CurrentUser::reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testConfirmPasswordSetsConfirmedPasswordAtAndRedirects()
    {
        $this->freeze($this->fake('dateTime'));
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('post', '/my/security/confirm', [
            'csrf' => $user->csrf,
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $session = auth\CurrentUser::session();
        $now = \Minz\Time::now();
        $this->assertSame($now->getTimestamp(), $session->confirmed_password_at->getTimestamp());
    }

    public function testConfirmPasswordRedirectsIfUserIsNotConnected()
    {
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'csrf' => 'a token',
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/my/security/confirm', [
            'csrf' => 'a token',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
    }

    public function testConfirmPasswordFailsIfCsrfIsInvalid()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('post', '/my/security/confirm', [
            'csrf' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $session = auth\CurrentUser::session();
        $this->assertNull($session->confirmed_password_at);
    }

    public function testConfirmPasswordFailsIfPasswordIsInvalid()
    {
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ], [], [
            'confirmed_password_at' => null,
        ]);

        $response = $this->appRun('post', '/my/security/confirm', [
            'csrf' => $user->csrf,
            'password' => 'not the password',
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $this->assertFlash('errors', [
            'password_hash' => 'The password is incorrect.',
        ]);
        $session = auth\CurrentUser::session();
        $this->assertNull($session->confirmed_password_at);
    }
}
