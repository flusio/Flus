<?php

namespace flusio\cli;

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
