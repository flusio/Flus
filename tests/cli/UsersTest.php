<?php

namespace flusio\cli;

use flusio\models;

class UsersTest extends \PHPUnit\Framework\TestCase
{
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
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $username = $faker->name;
        $email = $faker->email;
        $password = $faker->password;

        $this->assertSame(0, $user_dao->count());

        $response = $this->appRun('cli', '/users/create', [
            'username' => $username,
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponse($response, 200, "User {$username} ({$email}) has been created");
        $this->assertSame(1, $user_dao->count());
        $db_user = $user_dao->listAll()[0];
        $this->assertSame($username, $db_user['username']);
        $this->assertSame($email, $db_user['email']);
        $this->assertNotNull($db_user['validated_at']);
    }

    public function testCreateCreatesABookmarksCollection()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $collection_dao = new models\dao\Collection();

        $this->assertSame(0, $collection_dao->count());

        $response = $this->appRun('cli', '/users/create', [
            'username' => $faker->name,
            'email' => $faker->email,
            'password' => $faker->password,
        ]);

        $this->assertSame(1, $collection_dao->count());
        $db_collection = $collection_dao->listAll()[0];
        $db_user = $user_dao->listAll()[0];
        $this->assertSame('bookmarks', $db_collection['type']);
        $this->assertSame($db_user['id'], $db_collection['user_id']);
    }

    public function testCreateFailsIfAnArgumentIsInvalid()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new models\dao\User();
        $email = $faker->email;
        $password = $faker->password;

        $response = $this->appRun('cli', '/users/create', [
            'email' => $email,
            'password' => $password,
        ]);

        $this->assertResponse($response, 400, 'User creation failed: The username is required.');
        $this->assertSame(0, $user_dao->count());
    }

    public function testCleanDeletesNotValidatedUsersOlderThan1Month()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new \flusio\models\dao\User();

        $this->freeze($faker->dateTime);
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean');

        $this->assertResponse($response, 200, '1 user has been deleted.');
        $user = $user_dao->find($user_id);
        $this->assertNull($user);
    }

    public function testCleanDontRemoveValidatedUsersOlderThan1Month()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new \flusio\models\dao\User();

        $this->freeze($faker->dateTime);
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => $faker->iso8601,
        ]);

        $response = $this->appRun('cli', '/users/clean');

        $this->assertResponse($response, 200, '0 users have been deleted.');
        $user = $user_dao->find($user_id);
        $this->assertNotNull($user);
    }

    public function testCleanDontRemoveNotValidatedUsersYoungerThan1Month()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new \flusio\models\dao\User();

        $this->freeze($faker->dateTime);
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(21, 'days')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean');

        $this->assertResponse($response, 200, '0 users have been deleted.');
        $user = $user_dao->find($user_id);
        $this->assertNotNull($user);
    }

    public function testCleanAcceptsASinceParameter()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new \flusio\models\dao\User();

        $this->freeze($faker->dateTime);
        $since = $faker->randomDigitNotNull;
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
        $user_older = $user_dao->find($user_id_older);
        $user_younger = $user_dao->find($user_id_younger);
        $this->assertNull($user_older);
        $this->assertNotNull($user_younger);
    }

    public function testCleanFailsIfSinceIsLessThan1()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new \flusio\models\dao\User();

        $this->freeze($faker->dateTime);
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean', [
            'since' => 0,
        ]);

        $this->assertResponse($response, 400, 'The `since` parameter must be greater or equal to 1.');
        $user = $user_dao->find($user_id);
        $this->assertNotNull($user);
    }

    public function testCleanFailsIfSinceIsNotAnInteger()
    {
        $faker = \Faker\Factory::create();
        $user_dao = new \flusio\models\dao\User();

        $this->freeze($faker->dateTime);
        $user_id = $this->create('user', [
            'created_at' => \Minz\Time::ago(1, 'month')->format(\Minz\Model::DATETIME_FORMAT),
            'validated_at' => null,
        ]);

        $response = $this->appRun('cli', '/users/clean', [
            'since' => '12foo',
        ]);

        $this->assertResponse($response, 400, 'The `since` parameter must be an integer.');
        $user = $user_dao->find($user_id);
        $this->assertNotNull($user);
    }
}
