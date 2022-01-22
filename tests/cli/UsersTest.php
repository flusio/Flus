<?php

namespace flusio\cli;

use flusio\models;
use flusio\utils;

class UsersTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    public function testIndexListsUsers()
    {
        $created_at_1 = $this->fake('dateTime');
        $validated_at_1 = $this->fake('dateTime');
        $email_1 = $this->fake('email');
        $user_id_1 = $this->create('user', [
            'created_at' => $created_at_1->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $validated_at_1->format(\Minz\Model::DATETIME_FORMAT),
            'email' => $email_1,
        ]);
        $created_at_2 = clone $created_at_1;
        $created_at_2->modify('+1 hour');
        $email_2 = $this->fake('email');
        $user_id_2 = $this->create('user', [
            'created_at' => $created_at_2->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
            'email' => $email_2,
        ]);

        $response = $this->appRun('cli', '/users');

        $this->assertResponse($response, 200);
        $expected_output = <<<TEXT
        {$user_id_1} {$created_at_1->format('Y-m-d')} {$email_1}
        {$user_id_2} {$created_at_2->format('Y-m-d')} {$email_2} (not validated)
        TEXT;
        $output = $response->render();
        $this->assertSame($expected_output, $output);
    }

    public function testIndexShowsIfNoUsers()
    {
        $response = $this->appRun('cli', '/users');

        $this->assertResponse($response, 200);
        $expected_output = 'No users';
        $output = $response->render();
        $this->assertSame($expected_output, $output);
    }

    public function testCreateCreatesAValidatedUser()
    {
        $username = $this->fake('name');
        $email = $this->fake('email');
        $password = $this->fake('password');

        $this->assertSame(0, models\User::count());

        $response = $this->appRun('cli', '/users/create', [
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponse($response, 200, "User {$username} ({$email}) has been created");
        $this->assertSame(1, models\User::count());
        $user = models\User::take();
        $this->assertSame($username, $user->username);
        $this->assertSame($email, $user->email);
        $this->assertNotNull($user->validated_at);
    }

    public function testCreateCreatesDefaultCollections()
    {
        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('cli', '/users/create', [
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(4, models\Collection::count());
        $user = models\User::take();
        $bookmarks = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $news = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'news',
        ]);
        $read_list = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $never_list = models\Collection::findBy([
            'user_id' => $user->id,
            'type' => 'never',
        ]);
        $this->assertNotNull($bookmarks);
        $this->assertNotNull($news);
        $this->assertNotNull($read_list);
        $this->assertNotNull($never_list);
    }

    public function testCreateFailsIfAnArgumentIsInvalid()
    {
        $email = $this->fake('email');
        $password = $this->fake('password');

        $response = $this->appRun('cli', '/users/create', [
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponse($response, 400, 'User creation failed: The username is required.');
        $this->assertSame(0, models\User::count());
    }

    public function testExportCreatesTheDataFileAndRendersCorrectly()
    {
        $tmp_path = \Minz\Configuration::$tmp_path;
        $current_path = $tmp_path . '/' . md5(rand());
        @mkdir($current_path, 0777, true);
        @chdir($current_path);
        $user_id = $this->create('user');

        $response = $this->appRun('cli', '/users/export', [
            'id' => $user_id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'User’s data have been exported successfully');
        $output = $response->render();
        $success = preg_match('/^.*\((?P<filepath>.*)\).$/', $output, $matches);
        $this->assertSame(1, $success, 'Output must match the regex');
        $this->assertTrue(file_exists($matches['filepath']), 'Data file must exist');
    }

    public function testExportFailsIfUserDoesNotExist()
    {
        $tmp_path = \Minz\Configuration::$tmp_path;
        $current_path = $tmp_path . '/' . md5(rand());
        @mkdir($current_path, 0777, true);
        @chdir($current_path);
        $user_id = utils\Random::hex(32);

        $response = $this->appRun('cli', '/users/export', [
            'id' => $user_id,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, "User {$user_id} doesn’t exist.");
    }

    public function testValidateValidatesUser()
    {
        $this->freeze($this->fake('dateTime'));
        $user_id = $this->create('user', [
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/validate', [
            'id' => $user_id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "User {$user_id} is now validated.");
        $user = models\User::find($user_id);
        $this->assertEquals(\Minz\Time::now(), $user->validated_at);
    }

    public function testValidateSetsSubscriptionAccountId()
    {
        \Minz\Configuration::$application['subscriptions_enabled'] = true;
        $email = $this->fake('email');
        $account_id = $this->fake('uuid');
        $expired_at = $this->fake('dateTime');
        $url = "https://next.flus.io/api/account?email={$email}";
        $this->mockHttpWithResponse($url, <<<TEXT
            HTTP/2 200
            Content-type: application/json

            {
                "id": "{$account_id}",
                "expired_at": "{$expired_at->format(\Minz\Model::DATETIME_FORMAT)}"
            }
            TEXT
        );
        $user_id = $this->create('user', [
            'email' => $email,
            'validated_at' => null,
            'subscription_account_id' => null,
        ]);

        $response = $this->appRun('cli', '/users/validate', [
            'id' => $user_id,
        ]);

        \Minz\Configuration::$application['subscriptions_enabled'] = false;

        $user = models\User::find($user_id);
        $this->assertSame($account_id, $user->subscription_account_id);
        $this->assertEquals($expired_at, $user->subscription_expired_at);
    }

    public function testValidateDeletesToken()
    {
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token_id = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'validated_at' => null,
            'validation_token' => $token_id,
        ]);

        $response = $this->appRun('cli', '/users/validate', [
            'id' => $user_id,
        ]);

        $token = models\Token::find($token_id);
        $user = models\User::find($user_id);
        $this->assertNull($token);
        $this->assertNull($user->validation_token);
    }

    public function testValidateFailsIfUserDoesNotExist()
    {
        $response = $this->appRun('cli', '/users/validate', [
            'id' => 'not-an-id',
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'User not-an-id doesn’t exist.');
    }

    public function testValidateFailsIfAlreadyValidated()
    {
        $user_id = $this->create('user', [
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('cli', '/users/validate', [
            'id' => $user_id,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, "User {$user_id} has already been validated.");
    }
}
