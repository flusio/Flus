<?php

namespace flusio;

class RegistrationsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
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

    public function testNewRedirectsToLoginIfRegistrationsAreClosed()
    {
        \Minz\Configuration::$application['registrations_opened'] = false;

        $response = $this->appRun('get', '/registration');

        \Minz\Configuration::$application['registrations_opened'] = true;
        $this->assertResponse($response, 302, '/login');
    }

    public function testCreateCreatesAUserAndRedirects()
    {
        $user_dao = new models\dao\User();

        $this->assertSame(0, $user_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 302, '/onboarding');
    }

    public function testCreateCreatesARegistrationValidationToken()
    {
        $this->freeze($this->fake('dateTime'));

        $user_dao = new models\dao\User();
        $token_dao = new models\dao\Token();

        $this->assertSame(0, $token_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        // it also creates a session token
        $this->assertSame(2, $token_dao->count());

        $user = new Models\User($user_dao->listAll()[0]);
        $token = new Models\Token($token_dao->findBy(['token' => $user->validation_token]));
        $this->assertEquals(\Minz\Time::fromNow(1, 'day'), $token->expired_at);
    }

    public function testCreateSendsAValidationEmail()
    {
        $token_dao = new models\dao\Token();
        $email = $this->fake('email');

        $this->assertEmailsCount(0);

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $this->assertEmailsCount(1);

        $token = new Models\Token($token_dao->listAll()[0]);
        $email_sent = \Minz\Tests\Mailer::take();
        $this->assertEmailSubject($email_sent, '[flusio] Confirm your account');
        $this->assertEmailContainsTo($email_sent, $email);
        $this->assertEmailContainsBody($email_sent, $token->token);
    }

    public function testCreateLogsTheUserIn()
    {
        $user_dao = new models\dao\User();
        $session_dao = new models\dao\Session();
        $email = $this->fake('email');

        $user = utils\CurrentUser::get();
        $this->assertNull($user);
        $this->assertSame(0, $session_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $user = utils\CurrentUser::get();
        $this->assertSame($email, $user->email);
        $this->assertSame(1, $session_dao->count());
    }

    public function testCreateReturnsACookie()
    {
        $user_dao = new models\dao\User();
        $session_dao = new models\dao\Session();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $db_session = $session_dao->listAll()[0];
        $cookie = $response->cookies()['flusio_session_token'];
        $this->assertSame($db_session['token'], $cookie['value']);
        $this->assertSame('Lax', $cookie['options']['samesite']);
    }

    public function testCreateTakesAcceptTermsIfExist()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
            'accept_terms' => true,
        ]);

        @unlink($terms_path);
        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 302, '/onboarding');
    }

    public function testCreateRedirectsToHomeIfConnected()
    {
        $this->login();

        $user_dao = new models\dao\User();

        $this->assertSame(1, $user_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 302, '/');
    }

    public function testCreateCreatesABookmarksCollection()
    {
        $collection_dao = new models\dao\Collection();

        $this->assertSame(0, $collection_dao->count());

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertResponse($response, 302, '/onboarding');
        $this->assertSame(1, $collection_dao->count());
        $db_collection = $collection_dao->listAll()[0];
        $user = utils\CurrentUser::get();
        $this->assertSame('bookmarks', $db_collection['type']);
        $this->assertSame($user->id, $db_collection['user_id']);
    }

    public function testCreateRedirectsIfRegistrationsAreClosed()
    {
        \Minz\Configuration::$application['registrations_opened'] = false;
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        \Minz\Configuration::$application['registrations_opened'] = true;
        $this->assertResponse($response, 302, '/login');
        $this->assertSame(0, $user_dao->count());
    }

    public function testCreateFailsIfCsrfIsWrong()
    {
        $user_dao = new models\dao\User();
        (new \Minz\CSRF())->generateToken();

        $response = $this->appRun('post', '/registration', [
            'csrf' => 'not the token',
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'A security verification failed');
    }

    public function testCreateFailsIfUsernameIsMissing()
    {
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The username is required');
    }

    public function testCreateFailsIfUsernameIsTooLong()
    {
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('sentence', 50, false),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The username must be less than 50 characters');
    }

    public function testCreateFailsIfEmailIsMissing()
    {
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The address email is required');
    }

    public function testCreateFailsIfEmailIsInvalid()
    {
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('word'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The address email is invalid');
    }

    public function testCreateFailsIfEmailAlreadyExistsAndValidated()
    {
        $user_dao = new models\dao\User();

        $email = $this->fake('email');
        $this->create('user', [
            'email' => $email,
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $email,
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, $user_dao->count());
        $this->assertResponse($response, 400, 'An account already exists with this email address');
    }

    public function testCreateFailsIfPasswordIsMissing()
    {
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
        ]);

        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'The password is required');
    }

    public function testCreateFailsIfAcceptTermsIsFalseAndTermsExist()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));
        $user_dao = new models\dao\User();

        $response = $this->appRun('post', '/registration', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
            'accept_terms' => false,
        ]);

        @unlink($terms_path);
        $this->assertSame(0, $user_dao->count());
        $this->assertResponse($response, 400, 'You must accept the terms of service');
    }
}
