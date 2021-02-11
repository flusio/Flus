<?php

namespace flusio\cli;

use flusio\models;

class UsersTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
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

    public function testCreateCreatesABookmarksCollection()
    {
        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('cli', '/users/create', [
            'username' => $this->fake('name'),
            'email' => $this->fake('email'),
            'password' => $this->fake('password'),
        ]);

        $this->assertSame(1, models\Collection::count());
        $collection = models\Collection::take();
        $user = models\User::take();
        $this->assertSame('bookmarks', $collection->type);
        $this->assertSame($user->id, $collection->user_id);
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

    public function testCleanDeletesNotValidatedUsersOlderThan1Month()
    {
        $this->freeze($this->fake('dateTime'));
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean');

        $this->assertResponse($response, 200, '1 user has been deleted.');
        $this->assertFalse(models\User::exists($user_id));
    }

    public function testCleanDontRemoveValidatedUsersOlderThan1Month()
    {
        $this->freeze($this->fake('dateTime'));
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('cli', '/users/clean');

        $this->assertResponse($response, 200, '0 users have been deleted.');
        $this->assertTrue(models\User::exists($user_id));
    }

    public function testCleanDontRemoveNotValidatedUsersYoungerThan1Month()
    {
        $this->freeze($this->fake('dateTime'));
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(21, 'days')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean');

        $this->assertResponse($response, 200, '0 users have been deleted.');
        $this->assertTrue(models\User::exists($user_id));
    }

    public function testCleanAcceptsASinceParameter()
    {
        $this->freeze($this->fake('dateTime'));
        $since = $this->fake('randomDigitNotNull');
        $user_id_older = $this->create('user', [
            'created_at' => \Minz\Time::ago($since, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);
        $user_id_younger = $this->create('user', [
            'created_at' => \Minz\Time::ago($since - 1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean', [
            'since' => $since,
        ]);

        $this->assertResponse($response, 200, '1 user has been deleted.');
        $this->assertFalse(models\User::exists($user_id_older));
        $this->assertTrue(models\User::exists($user_id_younger));
    }

    public function testCleanFailsIfSinceIsLessThan1()
    {
        $this->freeze($this->fake('dateTime'));
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean', [
            'since' => 0,
        ]);

        $this->assertResponse($response, 400, 'The `since` parameter must be greater or equal to 1.');
        $this->assertTrue(models\User::exists($user_id));
    }

    public function testCleanFailsIfSinceIsNotAnInteger()
    {
        $this->freeze($this->fake('dateTime'));
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean', [
            'since' => '12foo',
        ]);

        $this->assertResponse($response, 400, 'The `since` parameter must be an integer.');
        $this->assertTrue(models\User::exists($user_id));
    }
}
