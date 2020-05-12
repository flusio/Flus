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

    public function testCreateCreatesARegistrationValidationToken()
    {
        $faker = \Faker\Factory::create();
        \Minz\Time::freeze($faker->dateTime);

        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $this->assertSame(0, $token_dao->count());

        $response = self::$application->run($request);

        $this->assertSame(1, $token_dao->count());

        $user = new Models\User($user_dao->listAll()[0]);
        $token = new Models\Token($token_dao->listAll()[0]);
        $this->assertSame($user->id, $token->user_id);
        $this->assertSame('registration_validation', $token->type);
        $this->assertEquals(\Minz\Time::fromNow(1, 'day'), $token->expired_at);

        \Minz\Time::unfreeze();
    }

    public function testCreateSendsAValidationEmail()
    {
        $faker = \Faker\Factory::create();
        $token_dao = new models\dao\Token();
        $email = $faker->email;
        $request = new \Minz\Request('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $email,
            'password' => $faker->password,
        ]);

        $this->assertSame(0, count(Tests\Mailer::$emails));

        $response = self::$application->run($request);

        $this->assertSame(1, count(Tests\Mailer::$emails));

        $token = new Models\Token($token_dao->listAll()[0]);
        $phpmailer = Tests\Mailer::$emails[0];
        $this->assertSame('[flusio] Confirm your registration', $phpmailer->Subject);
        $this->assertContains($email, $phpmailer->getToAddresses()[0]);
        $this->assertStringContainsString($token->token, $phpmailer->Body);
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

    public function testValidationRendersCorrectlyAndValidatesRegistration()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        \Minz\Time::freeze($faker->dateTime());

        $user_id = self::$factories['users']->create([
            'validated_at' => null,
        ]);
        $token = self::$factories['tokens']->create([
            'type' => 'registration_validation',
            'expired_at' => \Minz\Time::fromNow($faker->randomNumber, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $user_id,
        ]);

        $request = new \Minz\Request('get', '/registration/validation', [
            't' => $token,
        ]);

        $response = self::$application->run($request);

        $this->assertResponse($response, 200, 'Your registration is now validated');
        $user = new models\User($user_dao->find($user_id));
        $this->assertEquals(\Minz\Time::now(), $user->validated_at);
    }

    public function testValidationRedirectsIfRegistrationAlreadyValidated()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        \Minz\Time::freeze($faker->dateTime());

        $user_id = self::$factories['users']->create([
            'validated_at' => $faker->iso8601,
        ]);
        $token = self::$factories['tokens']->create([
            'type' => 'registration_validation',
            'expired_at' => \Minz\Time::fromNow($faker->randomNumber, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $user_id,
        ]);

        $request = new \Minz\Request('get', '/registration/validation', [
            't' => $token,
        ]);

        $response = self::$application->run($request);

        $this->assertResponse($response, 302, null, [
            'Location' => '/',
        ]);
    }

    public function testValidationFailsIfTokenHasExpired()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        \Minz\Time::freeze($faker->dateTime());

        $user_id = self::$factories['users']->create([
            'validated_at' => null,
        ]);
        $token = self::$factories['tokens']->create([
            'type' => 'registration_validation',
            'expired_at' => \Minz\Time::ago($faker->randomNumber, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $user_id,
        ]);

        $request = new \Minz\Request('get', '/registration/validation', [
            't' => $token,
        ]);

        $response = self::$application->run($request);

        $this->assertResponse($response, 400, 'The token has expired');
        $user = new models\User($user_dao->find($user_id));
        $this->assertNull($user->validated_at);
    }

    public function testValidationFailsIfTokenDoesNotExist()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        \Minz\Time::freeze($faker->dateTime());

        $user_id = self::$factories['users']->create([
            'validated_at' => null,
        ]);
        $token = self::$factories['tokens']->create([
            'type' => 'registration_validation',
            'expired_at' => \Minz\Time::ago($faker->randomNumber, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $user_id,
        ]);

        $request = new \Minz\Request('get', '/registration/validation', [
            't' => 'not the token',
        ]);

        $response = self::$application->run($request);

        $this->assertResponse($response, 404, 'The token doesn’t exist');
        $user = new models\User($user_dao->find($user_id));
        $this->assertNull($user->validated_at);
    }
}
