<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\utils;
use tests\factories\UserFactory;

class SessionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testNewRendersCorrectly(): void
    {
        $response = $this->appRun('GET', '/login');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Login');
        $this->assertResponseTemplateName($response, 'sessions/new.phtml');
    }

    public function testNewRedirectsToHomeIfConnected(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/login');

        $this->assertResponseCode($response, 302, '/');
    }

    public function testNewRedirectsToRedirectToIfConnectedAndParamIsPassed(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/login', [
            'redirect_to' => '/about',
        ]);

        $this->assertResponseCode($response, 302, '/about');
    }

    public function testNewShowsDemoCredentialsIfDemo(): void
    {
        \App\Configuration::$application['demo'] = true;

        $response = $this->appRun('GET', '/login');

        \App\Configuration::$application['demo'] = false;
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'demo@flus.io');
    }

    public function testCreateLogsTheUserInAndRedirectToHome(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $current_user = auth\CurrentUser::get();
        $this->assertNotNull($current_user);
        $this->assertSame($user->id, $current_user->id);
    }

    public function testCreateReturnsACookie(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertInstanceOf(\Minz\Response::class, $response);
        $session = models\Session::take();
        $this->assertNotNull($session);
        $cookie = $response->cookies()['session_token'];
        $this->assertSame($session->token, $cookie['value']);
        $this->assertSame('Lax', $cookie['options']['samesite']);
    }

    public function testCreateCreatesASessionValidForOneMonth(): void
    {
        $this->freeze();

        /** @var string */
        $ip = $this->fake('ipv6');
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(0, models\Session::count());

        $response = $this->appRun(
            'POST',
            '/login',
            parameters: [
                'csrf_token' => $this->csrfToken(forms\Login::class),
                'email' => $email,
                'password' => $password,
            ],
            headers: [
                'User-Agent' => 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:76.0) Gecko/20100101 Firefox/76.0',
            ],
            server: [
                'REMOTE_ADDR' => $ip,
            ],
        );

        $this->assertSame(1, models\Session::count());

        $session = models\Session::take();
        $this->assertNotNull($session);
        $token = models\Token::find($session->token);
        $this->assertNotNull($token);
        $this->assertSame($user->id, $session->user_id);
        $this->assertSame('Firefox on Linux', $session->name);
        $this->assertSame(utils\Ip::mask($ip), $session->ip);
        $this->assertEquals(
            \Minz\Time::fromNow(1, 'month')->getTimestamp(),
            $token->expired_at->getTimestamp(),
        );
    }

    public function testCreateDoesNotCreateASessionIfConnected(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = $this->login([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $number_tokens = models\Session::count();

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertSame($number_tokens, models\Session::count());
        $this->assertResponseCode($response, 302, '/');
    }

    public function testCreateRedirectsToRedirectTo(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => $email,
            'password' => $password,
            'redirect_to' => '/about',
        ]);

        $this->assertResponseCode($response, 302, '/about');
    }

    public function testCreateForcesRedirectionOnCurrentInstance(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);
        $redirect_to = 'https://example.com/about';

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => $email,
            'password' => $password,
            'redirect_to' => $redirect_to,
        ]);

        $this->assertResponseCode($response, 302, '/');
    }

    public function testCreateIsCaseInsensitive(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $current_user = auth\CurrentUser::get();
        $this->assertNull($current_user);

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => strtoupper($email),
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $current_user = auth\CurrentUser::get();
        $this->assertNotNull($current_user);
        $this->assertSame($user->id, $current_user->id);
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        \App\Csrf::generate();
        $response = $this->appRun('POST', '/login', [
            'csrf_token' => 'not the token',
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Session::count());
    }

    public function testCreateFailsIfEmailDoesNotMatchAUser(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => 'not@the.email',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'We can’t find any account with this email address');
        $this->assertSame(0, models\Session::count());
    }

    public function testCreateFailsIfEmailIsSupportUserEmail(): void
    {
        $email = \App\Configuration::$application['support_email'];
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'What are you trying to do? You can’t login to the support account.');
        $this->assertSame(0, models\Session::count());
    }

    public function testCreateFailsIfEmailIsInvalid(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => 'foo',
            'password' => $password,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The address email is invalid');
        $this->assertSame(0, models\Session::count());
    }

    public function testCreateFailsIfPasswordDoesNotMatch(): void
    {
        /** @var string */
        $email = $this->fake('email');
        /** @var string */
        $password = $this->fake('password');
        $user = UserFactory::create([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('POST', '/login', [
            'csrf_token' => $this->csrfToken(forms\Login::class),
            'email' => $email,
            'password' => 'not the password',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The password is incorrect');
        $this->assertSame(0, models\Session::count());
    }

    public function testDeleteDeletesCurrentSessionAndRedirectsToHome(): void
    {
        $user = $this->login();

        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('POST', '/logout', [
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\Session::count());
        $this->assertNull(auth\CurrentUser::get());
    }

    public function testDeleteReturnsACookie(): void
    {
        $user = $this->login();

        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('POST', '/logout', [
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertInstanceOf(\Minz\Response::class, $response);
        $cookie = $response->cookies()['session_token'];
        $this->assertSame('', $cookie['value']);
        $this->assertTrue($cookie['options']['expires'] < \Minz\Time::now()->getTimestamp());
    }

    public function testDeleteRedirectsToHomeIfNotConnected(): void
    {
        $this->assertSame(0, models\Session::count());

        $response = $this->appRun('POST', '/logout', [
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $this->login();

        $response = $this->appRun('POST', '/logout', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertSame(1, models\Session::count());
    }

    public function testChangeLocaleSetsSessionLocale(): void
    {
        $this->assertArrayNotHasKey('locale', $_SESSION);

        $response = $this->appRun('POST', '/sessions/locale', [
            'csrf' => \App\Csrf::generate(),
            'locale' => 'fr_FR',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('fr_FR', $_SESSION['locale']);
    }

    public function testChangeLocaleRedirectsToRedirectTo(): void
    {
        $response = $this->appRun('POST', '/sessions/locale', [
            'csrf' => \App\Csrf::generate(),
            'locale' => 'fr_FR',
            'redirect_to' => '/registration',
        ]);

        $this->assertResponseCode($response, 302, '/registration');
    }

    public function testChangeLocaleWithWrongCsrfDoesntSetsSessionLocale(): void
    {
        $response = $this->appRun('POST', '/sessions/locale', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }

    public function testChangeLocaleWithUnsupportedLocaleDoesntSetsSessionLocale(): void
    {
        $response = $this->appRun('POST', '/sessions/locale', [
            'csrf' => \App\Csrf::generate(),
            'locale' => 'zu',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }
}
