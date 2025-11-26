<?php

namespace App\controllers\my;

use App\auth;
use App\forms;
use App\models;
use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class SecurityTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersCorrectly(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 0, 15);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login(confirmed_password_at: $confirmed_at);

        $response = $this->appRun('GET', '/my/security');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'You can change your login details here');
        $this->assertResponseTemplateName($response, 'my/security/show.html.twig');
    }

    public function testShowRedirectsIfPasswordIsNotConfirmed(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 16, 9000);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login(confirmed_password_at: $confirmed_at);

        $response = $this->appRun('GET', '/my/security');

        $this->assertResponseCode($response, 302, '/my/security/confirmation?redirect_to=%2Fmy%2Fsecurity');
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
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: \Minz\Time::now());

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
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
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: \Minz\Time::now());

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
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
            'password_hash' => models\User::passwordHash($old_password),
            'reset_token' => $token->token,
        ], confirmed_password_at: \Minz\Time::now());

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
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
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: \Minz\Time::now());
        $current_session = auth\CurrentUser::session();
        $session = SessionFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertSame(2, models\Session::count());

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
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
            'email' => $old_email,
            'password_hash' => models\User::passwordHash($old_password),
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity');
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateRedirectsIfPasswordIsNotConfirmed(): void
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
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: null);

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security/confirmation?redirect_to=%2Fmy%2Fsecurity');
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
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: \Minz\Time::now());

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => 'not the token',
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertResponseTemplateName($response, 'my/security/show.html.twig');
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
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: \Minz\Time::now());
        UserFactory::create([
            'email' => $new_email,
        ]);

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'An account already exists with this email address');
        $this->assertResponseTemplateName($response, 'my/security/show.html.twig');
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
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: \Minz\Time::now());

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
            'email' => $new_email,
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is invalid');
        $this->assertResponseTemplateName($response, 'my/security/show.html.twig');
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
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: \Minz\Time::now());

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
            'email' => '',
            'password' => $new_password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is required');
        $this->assertResponseTemplateName($response, 'my/security/show.html.twig');
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testUpdateFailsIfChangingDemoAccount(): void
    {
        \App\Configuration::$application['demo'] = true;

        $old_email = models\User::DEMO_EMAIL;
        /** @var string */
        $new_email = $this->fake('email');
        /** @var string */
        $old_password = $this->fake('password');
        /** @var string */
        $new_password = $this->fake('password');
        $user = $this->login([
            'email' => $old_email,
            'password_hash' => models\User::passwordHash($old_password),
        ], confirmed_password_at: \Minz\Time::now());

        $response = $this->appRun('POST', '/my/security', [
            'csrf_token' => $this->csrfToken(forms\security\Credentials::class),
            'email' => $new_email,
            'password' => $new_password,
        ]);

        \App\Configuration::$application['demo'] = false;
        $this->assertResponseCode($response, 400, '/my/security');
        $this->assertResponseContains($response, 'Sorry but you cannot do that in the demo 😉');
        $user = $user->reload();
        $this->assertSame($old_email, $user->email);
        $this->assertTrue($user->verifyPassword($old_password));
    }

    public function testConfirmationRendersCorrectly(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var int */
        $minutes = $this->fake('numberBetween', 16, 9000);
        $confirmed_at = \Minz\Time::ago($minutes, 'minutes');
        $this->login(confirmed_password_at: $confirmed_at);

        $response = $this->appRun('GET', '/my/security/confirmation', [
            'redirect_to' => '/my/security'
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We need you to confirm your password');
        $this->assertResponseTemplateName($response, 'my/security/confirmation.html.twig');
    }

    public function testConfirmationRedirectsIfUserIsNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/security/confirmation', [
            'redirect_to' => '/my/security'
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity%2Fconfirmation');
    }

    public function testConfirmSetsConfirmedPasswordAtAndRedirects(): void
    {
        /** @var \DateTimeImmutable */
        $now = $this->fake('dateTime');
        $this->freeze($now);
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => models\User::passwordHash($password),
        ], confirmed_password_at: null);

        $response = $this->appRun('POST', '/my/security/confirmation', [
            'redirect_to' => '/my/security',
            'csrf_token' => $this->csrfToken(forms\security\ConfirmPassword::class),
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/my/security');
        $session = auth\CurrentUser::session();
        $now = \Minz\Time::now();
        $this->assertEquals($now, $session->confirmed_password_at);
    }

    public function testConfirmRedirectsIfUserIsNotConnected(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'password_hash' => models\User::passwordHash($password),
        ]);

        $response = $this->appRun('POST', '/my/security/confirmation', [
            'redirect_to' => '/my/security',
            'csrf_token' => $this->csrfToken(forms\security\ConfirmPassword::class),
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Fsecurity%2Fconfirmation');
    }

    public function testConfirmFailsIfCsrfIsInvalid(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => models\User::passwordHash($password),
        ], confirmed_password_at: null);

        $response = $this->appRun('POST', '/my/security/confirmation', [
            'redirect_to' => '/my/security',
            'csrf_token' => 'not the token',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertResponseTemplateName($response, 'my/security/confirmation.html.twig');
        $session = auth\CurrentUser::session();
        $this->assertNull($session->confirmed_password_at);
    }

    public function testConfirmFailsIfPasswordIsInvalid(): void
    {
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'password_hash' => models\User::passwordHash($password),
        ], confirmed_password_at: null);

        $response = $this->appRun('POST', '/my/security/confirmation', [
            'redirect_to' => '/my/security',
            'csrf_token' => $this->csrfToken(forms\security\ConfirmPassword::class),
            'password' => 'not the password',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The password is incorrect.');
        $this->assertResponseTemplateName($response, 'my/security/confirmation.html.twig');
        $session = auth\CurrentUser::session();
        $this->assertNull($session->confirmed_password_at);
    }
}
