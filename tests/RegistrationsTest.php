<?php

namespace flusio;

class RegistrationsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\MailerAsserts;

    public function testNewRendersCorrectly()
    {
        $response = $this->appRun('get', '/registration');

        $this->assertResponse($response, 200);
    }

    public function testNewRedirectsToHomeIfConnected()
    {
        $this->login();

        $response = $this->appRun('get', '/registration');

        $this->assertResponse($response, 302, '/');
    }

    public function testCreateCreatesAUserAndRedirects()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $this->assertSame(0, $user_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 302, '/');
    }

    public function testCreateCreatesARegistrationValidationToken()
    {
        $faker = \Faker\Factory::create();
        $this->freeze($faker->dateTime);

        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();

        $this->assertSame(0, $token_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        // it also creates a session token
        $this->assertSame(2, $token_dao->count());

        $user = new Models\User($user_dao->listAll()[0]);
        $token = new Models\Token($token_dao->findBy(['token' => $user->validation_token]));
        $this->assertEquals(\Minz\Time::fromNow(1, 'day'), $token->expired_at);
    }

    public function testCreateSendsAValidationEmail()
    {
        $faker = \Faker\Factory::create();
        $token_dao = new models\dao\Token();
        $email = $faker->email;

        $this->assertEmailsCount(0);

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $email,
            'password' => $faker->password,
        ]);

        $this->assertEmailsCount(1);

        $token = new Models\Token($token_dao->listAll()[0]);
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertEmailSubject($email_sent, '[flusio] Confirm your registration');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token->token);
    }

    public function testCreateLogsTheUserIn()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $session_dao = new models\dao\Session();
        $email = $faker->email;

        $user = utils\CurrentUser::get();
        $this->assertNull($user);
        $this->assertSame(0, $session_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $email,
            'password' => $faker->password,
        ]);

        $user = utils\CurrentUser::get();
        $this->assertSame($email, $user->email);
        $this->assertSame(1, $session_dao->count());
    }

    public function testCreateRedirectsToHomeIfConnected()
    {
        $this->login();

        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $this->assertSame(1, $user_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 302, '/');
    }

    public function testCreateFailsIfCsrfIsWrong()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        (new \Minz\CSRF())->generateToken();

        $response = $this->appRun('post', '/registration', [
            'csrf' => 'not the token',
            'username' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'A security verification failed');
    }

    public function testCreateFailsIfUsernameIsMissing()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The username is required');
    }

    public function testCreateFailsIfUsernameIsTooLong()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->sentence(50, false),
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The username must be less than 50 characters');
    }

    public function testCreateFailsIfEmailIsMissing()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The address email is required');
    }

    public function testCreateFailsIfEmailIsInvalid()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->word,
            'password' => $faker->password,
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The address email is invalid');
    }

    public function testCreateFailsIfEmailAlreadyExistsAndValidated()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $email = $faker->email;
        $this->create('user', [
            'email' => $email,
            'validated_at' => $faker->iso8601(),
        ]);

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $email,
            'password' => $faker->password,
        ]);

        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 400, 'An account already exists with this email address');
    }

    public function testCreateFailsIfPasswordIsMissing()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $faker->name,
            'email' => $faker->email,
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The password is required');
    }

    public function testValidationRendersCorrectlyAndValidatesRegistration()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $this->freeze($faker->dateTime());

        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/registration/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 200, 'Your registration is now validated');
        $user = new models\User($user_dao->find($user_id));
        $this->assertEquals(\Minz\Time::now(), $user->validated_at);
    }

    public function testValidationRedirectsIfRegistrationAlreadyValidated()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $this->freeze($faker->dateTime());

        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => $faker->iso8601,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/registration/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 302, '/');
    }

    public function testValidationDeletesToken()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();
        $this->freeze($faker->dateTime());

        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token_id = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token_id,
        ]);

        $response = $this->appRun('get', '/registration/validation', [
            't' => $token_id,
        ]);

        $token = $token_dao->find($token_id);
        $user = new models\User($user_dao->find($user_id));
        $this->assertNull($token);
        $this->assertNull($user->validation_token);
    }

    public function testValidationFailsIfTokenHasExpired()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $this->freeze($faker->dateTime());

        $expired_at = \Minz\Time::ago($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/registration/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 400, 'The token has expired or has been invalidated');
        $user = new models\User($user_dao->find($user_id));
        $this->assertNull($user->validated_at);
    }

    public function testValidationFailsIfTokenHasBeenInvalidated()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $this->freeze($faker->dateTime());

        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'invalidated_at' => $faker->iso8601,
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/registration/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 400, 'The token has expired or has been invalidated');
        $user = new models\User($user_dao->find($user_id));
        $this->assertNull($user->validated_at);
    }

    public function testValidationFailsIfTokenIsNotAssociatedToAUser()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $response = $this->appRun('get', '/registration/validation', [
            't' => $token,
        ]);

        $this->assertResponse($response, 404, 'The token doesn’t exist');
    }

    public function testValidationFailsIfTokenDoesNotExist()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $this->freeze($faker->dateTime());

        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('get', '/registration/validation', [
            't' => 'not the token',
        ]);

        $this->assertResponse($response, 404, 'The token doesn’t exist');
        $user = new models\User($user_dao->find($user_id));
        $this->assertNull($user->validated_at);
    }

    public function testResendValidationEmailSendsAnEmailAndRedirects()
    {
        $faker = \Faker\Factory::create();
        $email = $faker->email;
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->login([
            'email' => $email,
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $this->assertEmailsCount(0);

        $response = $this->appRun('post', '/registration/validation/email', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertFlash('status', 'validation_email_sent');
        $this->assertEmailsCount(1);
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertEmailSubject($email_sent, '[flusio] Confirm your registration');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token);
    }

    public function testResendValidationEmailRedirectsToRedictTo()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(31, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $response = $this->appRun('post', '/registration/validation/email', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'redirect_to' => '/about',
        ]);

        $this->assertResponse($response, 302, '/about');
        $this->assertFlash('status', 'validation_email_sent');
    }

    public function testResendValidationEmailCreatesANewTokenIfExpiresSoon()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();

        $expired_at = \Minz\Time::fromNow($faker->numberBetween(0, 30), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user = $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $number_tokens = $token_dao->count();

        $response = $this->appRun('post', '/registration/validation/email', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertSame($number_tokens + 1, $token_dao->count());
        $user = new models\User($user_dao->find($user->id)); // reload the user
        $this->assertNotSame($user->validation_token, $token);
    }

    public function testResendValidationEmailCreatesANewTokenIfInvalidated()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();

        $expired_at = \Minz\Time::fromNow($faker->numberBetween(31, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'invalidated_at' => $faker->iso8601
        ]);
        $user = $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);

        $number_tokens = $token_dao->count();

        $response = $this->appRun('post', '/registration/validation/email', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertSame($number_tokens + 1, $token_dao->count());
        $user = new models\User($user_dao->find($user->id)); // reload the user
        $this->assertNotSame($user->validation_token, $token);
    }

    public function testResendValidationEmailRedirectsSilentlyIfAlreadyValidated()
    {
        $faker = \Faker\Factory::create();
        $this->login([
            'validated_at' => $faker->iso8601,
        ]);

        $response = $this->appRun('post', '/registration/validation/email', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, '/');
        $this->assertEmailsCount(0);
    }

    public function testResendValidationEmailFailsIfCsrfIsInvalid()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $this->login([
            'validated_at' => null,
            'validation_token' => $token,
        ]);
        (new \Minz\CSRF())->generateToken();

        $response = $this->appRun('post', '/registration/validation/email', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertEmailsCount(0);
    }

    public function testResendValidationEmailFailsIfUserNotConnected()
    {
        $response = $this->appRun('post', '/registration/validation/email', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 401, 'You must be connected to see this page.');
        $this->assertEmailsCount(0);
    }
}
