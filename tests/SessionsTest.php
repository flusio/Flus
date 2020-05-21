<?php

namespace flusio;

class SessionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
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
            'redirect_to' => 'about',
        ]);

        $this->assertResponse($response, 302, '/about');
    }

    public function testCreateLogsTheUserInAndRedirectToHome()
    {
        $faker = \Faker\Factory::create();
        $email = $faker->email;
        $password = $faker->password;
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $user = utils\CurrentUser::get();
        $this->assertNull($user);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/');
        $user = utils\CurrentUser::get();
        $this->assertSame($user_id, $user->id);
    }

    public function testCreateCreatesASessionValidForOneMonth()
    {
        $session_dao = new models\dao\Session();
        $token_dao = new models\dao\Token();
        $faker = \Faker\Factory::create();

        $this->freeze($faker->dateTime);

        $ip = $faker->ipv6;
        $email = $faker->email;
        $password = $faker->password;
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->assertSame(0, $session_dao->count());

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => $password,
        ], [
            'REMOTE_ADDR' => $ip,
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:76.0) Gecko/20100101 Firefox/76.0',
        ]);

        $this->assertSame(1, $session_dao->count());

        $session = new models\Session($session_dao->listAll()[0]);
        $token = new models\Token($token_dao->find($session->token));
        $this->assertSame($user_id, $session->user_id);
        $this->assertSame('Firefox on Linux', $session->name);
        $this->assertSame($ip, $session->ip);
        $this->assertEquals(\Minz\Time::fromNow(1, 'month'), $token->expired_at);
    }

    public function testCreateDoesNotCreateASessionIfConnected()
    {
        $session_dao = new models\dao\Session();
        $faker = \Faker\Factory::create();

        $email = $faker->email;
        $password = $faker->password;
        $this->login([
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $number_tokens = $session_dao->count();

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertSame($number_tokens, $session_dao->count());
        $this->assertResponse($response, 302, '/');
    }

    public function testCreateRedirectsToRedirectTo()
    {
        $faker = \Faker\Factory::create();
        $email = $faker->email;
        $password = $faker->password;
        $user_id = $this->create('user', [
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $email,
            'password' => $password,
            'redirect_to' => 'about',
        ]);

        $this->assertResponse($response, 302, '/about');
    }

    public function testCreateIsCaseInsensitive()
    {
        $faker = \Faker\Factory::create();
        $email = $faker->email;
        $password = $faker->password;
        $user_id = $this->create('user', [
            'email' => strtolower($email),
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $user = utils\CurrentUser::get();
        $this->assertNull($user);

        $response = $this->appRun('post', '/login', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => strtoupper($email),
            'password' => $password,
        ]);

        $this->assertResponse($response, 302, '/');
        $user = utils\CurrentUser::get();
        $this->assertSame($user_id, $user->id);
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $session_dao = new models\dao\Session();
        $faker = \Faker\Factory::create();

        $email = $faker->email;
        $password = $faker->password;
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
        $this->assertSame(0, $session_dao->count());
    }

    public function testCreateFailsIfEmailDoesNotMatchAUser()
    {
        $session_dao = new models\dao\Session();
        $faker = \Faker\Factory::create();

        $email = $faker->email;
        $password = $faker->password;
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
        $this->assertSame(0, $session_dao->count());
    }

    public function testCreateFailsIfPasswordDoesNotMatch()
    {
        $session_dao = new models\dao\Session();
        $faker = \Faker\Factory::create();

        $email = $faker->email;
        $password = $faker->password;
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
        $this->assertSame(0, $session_dao->count());
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

    public function testChangeLocaleSavesTheLocaleInUserIfConnected()
    {
        $user_dao = new models\dao\User();
        $user = $this->login([
            'locale' => 'en_GB',
        ]);

        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
        ]);

        $user = new models\User($user_dao->find($user->id)); // reload the user
        $this->assertSame('fr_FR', $user->locale);
    }

    public function testChangeLocaleRedirectsToRedirectTo()
    {
        $response = $this->appRun('post', '/sessions/locale', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'locale' => 'fr_FR',
            'redirect_to' => 'registration',
        ]);

        $this->assertResponse($response, 302, '/registration');
    }

    public function testChangeLocaleWithWrongCsrfDoesntSetsSessionLocale()
    {
        (new \Minz\CSRF())->generateToken();

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
