<?php

namespace flusio;

use Minz\Tests;

class UsersTest extends Tests\IntegrationTestCase
{
    public function testRegistrationRendersCorrectly()
    {
        $request = new \Minz\Request('get', '/registration');

        $response = self::$application->run($request);

        $this->assertResponse($response, 200);
    }

    public function testCreateCreatesAUserAndRedirects()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $this->assertSame(0, $user_dao->count());

        $response = self::$application->run($request);

        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 302, null, [
            'Location' => '/',
        ]);
    }

    public function testCreateFailsIfCsrfIsWrong()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        (new \Minz\CSRF())->generateToken();
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => 'not the token',
            'username' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $response = self::$application->run($request);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'A security verification failed');
    }

    public function testCreateFailsIfUsernameIsMissing()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $response = self::$application->run($request);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The username is required');
    }

    public function testCreateFailsIfUsernameIsTooLong()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->sentence(50, false),
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $response = self::$application->run($request);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The username must be less than 50 characters');
    }

    public function testCreateFailsIfEmailIsMissing()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
        ]);

        $response = self::$application->run($request);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The address email is required');
    }

    public function testCreateFailsIfEmailIsInvalid()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->word,
            'password' => $faker->password,
        ]);

        $response = self::$application->run($request);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The address email is invalid');
    }

    public function testCreateFailsIfEmailAlreadyExistsAndValidated()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $email = $faker->email;
        self::$factories['users']->create([
            'email' => $email,
            'validated_at' => $faker->iso8601(),
        ]);

        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $email,
            'password' => $faker->password,
        ]);

        $response = self::$application->run($request);

        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 400, 'An account already exists with this email address');
    }

    public function testCreateFailsIfPasswordIsMissing()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->email,
        ]);

        $response = self::$application->run($request);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The password is required');
    }
}
