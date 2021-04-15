<?php

namespace flusio\controllers;

use flusio\auth;
use flusio\models;

class SessionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testNewRendersCorrectly()
    {
        $response = $this->appRun('get', '/login');

        $this->assertResponse($response, 200, 'Login');
        $this->assertPointer($response, 'sessions/new.phtml');
    }

    public function testNewRedirectsToHomeIfConnected()
    {
        $this->login();

        $response = $this->appRun('get', '/login');

        $this->assertResponse($response, 302, '/');
    }

    public function testNewRedirectsToRedirectToIfConnectedAndParamIsPassed()
    {
        $this->login();

        $response = $this->appRun('get', '/login', [
            'redirect_to' => '/about',
        ]);

        $this->assertResponse($response, 302, '/about');
    }

    public function testNewShowsDemoCredentialsIfDemo()
    {
        \Minz\Configuration::$application['demo'] = true;

        $response = $this->appRun('get', '/login');

        \Minz\Configuration::$application['demo'] = false;
        $this->assertResponse($response, 200, 'demo@flus.io');
    }

    public function testCreateLogsTheUserInAndRedirectToHome()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $user = auth\CurrentUser::get();
        $this->assertNull($user);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/');
        $user = auth\CurrentUser::get();
        $this->assertSame($user_id, $user->id);
    }

    public function testCreateReturnsACookie()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => $password,
        ]);

        $session = models\Session::take();
        $cookie = $response->cookies()['flusio_session_token'];
        $this->assertSame($session->token, $cookie['value']);
        $this->assertSame('Lax', $cookie['options']['samesite']);
    }

    public function testCreateCreatesASessionValidForOneMonth()
    {
        $this->freeze($this->fake('dateTime'));

        $ip = $this->fake('ipv6');
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(0, models\Session::count());

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => $password,
        ], [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:76.0) Gecko/20100101 Firefox/76.0',
        ]);

        $this->assertSame(1, models\Session::count());

        $session = models\Session::take();
        $token = models\Token::find($session->token);
        $this->assertSame($user_id, $session->user_id);
        $this->assertSame('Firefox on Linux', $session->name);
        $this->assertSame($ip, $session->ip);
        $this->assertEquals(\Minz\Time::fromNow(1, 'month'), $token->expired_at);
    }

    public function testCreateDoesNotCreateASessionIfConnected()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user = $this->login([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $number_tokens = models\Session::count();

        $response = $this->appRun('post', '/login', [
            'csrf' => $user->csrf,
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertSame($number_tokens, models\Session::count());
        $this->assertResponse($response, 302, '/');
    }

    public function testCreateRedirectsToRedirectTo()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => $password,
            'redirect_to' => '/about',
        ]);

        $this->assertResponse($response, 302, '/about');
    }

    public function testCreateIsCaseInsensitive()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'email' => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $user = auth\CurrentUser::get();
        $this->assertNull($user);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => strtoupper($email),
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/');
        $user = auth\CurrentUser::get();
        $this->assertSame($user_id, $user->id);
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        (new \Minz\CSRF())->generateToken();
        $response = $this->appRun('post', '/login', [
            'csrf' => 'not the token',
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, models\Session::count());
    }

    public function testCreateFailsIfEmailDoesNotMatchAUser()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => 'not@the.email',
            'password' => $password,
        ]);

        $this->assertResponse($response, 400, 'We can’t find any account with this email address');
        $this->assertSame(0, models\Session::count());
    }

    public function testCreateFailsIfPasswordDoesNotMatch()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => 'not the password',
        ]);

        $this->assertResponse($response, 400, 'The password is incorrect');
        $this->assertSame(0, models\Session::count());
    }

    public function testDeleteDeletesCurrentSessionAndRedirectsToHome()
    {
        $user = $this->login();

        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('post', '/logout', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertSame(0, models\Session::count());
        $this->assertNull(auth\CurrentUser::get());
    }

    public function testDeleteReturnsACookie()
    {
        $user = $this->login();

        $this->assertSame(1, models\Session::count());

        $response = $this->appRun('post', '/logout', [
            'csrf' => $user->csrf,
        ]);

        $cookie = $response->cookies()['flusio_session_token'];
        $this->assertSame('', $cookie['value']);
        $this->assertTrue($cookie['options']['expires'] < \Minz\Time::now()->getTimestamp());
    }

    public function testDeleteRedirectsToHomeIfNotConnected()
    {
        $this->assertSame(0, models\Session::count());

        $response = $this->appRun('post', '/logout', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, '/');
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $this->login();

        $response = $this->appRun('post', '/logout', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertSame(1, models\Session::count());
    }

    public function testChangeLocaleSetsSessionLocale()
    {
        $this->assertArrayNotHasKey('locale', $_SESSION);

        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertSame('fr_FR', $_SESSION['locale']);
    }

    public function testChangeLocaleRedirectsToRedirectTo()
    {
        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
            'redirect_to' => '/registration',
        ]);

        $this->assertResponse($response, 302, '/registration');
    }

    public function testChangeLocaleWithWrongCsrfDoesntSetsSessionLocale()
    {
        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => 'not the token',
            'locale' => 'fr_FR',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }

    public function testChangeLocaleWithUnsupportedLocaleDoesntSetsSessionLocale()
    {
        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'zu',
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertArrayNotHasKey('locale', $_SESSION);
    }
}
