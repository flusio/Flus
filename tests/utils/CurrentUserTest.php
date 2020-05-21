<?php

namespace flusio\utils;

use flusio\models;

class CurrentUserTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;

    /**
     * @before
     */
    public function resetCurrentUser()
    {
        CurrentUser::reset();
    }

    public function testSetSessionTokenSetsTheTokenInSession()
    {
        $token = $this->create('token');

        CurrentUser::setSessionToken($token);

        $this->assertSame($token, $_SESSION['current_session_token']);
    }

    public function testGetReturnsTheUser()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user');
        $this->create('session', [
            'user_id' => $user_id,
            'token' => $token,
        ]);
        CurrentUser::setSessionToken($token);

        $user = CurrentUser::get();

        $this->assertSame($user_id, $user->id);
    }

    public function testGetReturnsNullIfSessionTokenIsNotInSession()
    {
        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsNullIfTheUserDoesNotExist()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        CurrentUser::setSessionToken($token);

        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsNullIfTokenHasExpired()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::ago($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user');
        $this->create('session', [
            'user_id' => $user_id,
            'token' => $token,
        ]);
        CurrentUser::setSessionToken($token);

        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsNullIfTokenIsInvalidated()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'invalidated_at' => $faker->iso8601,
        ]);
        $user_id = $this->create('user');
        $this->create('session', [
            'user_id' => $user_id,
            'token' => $token,
        ]);
        CurrentUser::setSessionToken($token);

        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsTheCorrectUserAfterChangingTheCurrentUser()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');

        $token_1 = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $token_2 = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id_1 = $this->create('user');
        $user_id_2 = $this->create('user');
        $this->create('session', [
            'user_id' => $user_id_1,
            'token' => $token_1,
        ]);
        $this->create('session', [
            'user_id' => $user_id_2,
            'token' => $token_2,
        ]);

        CurrentUser::setSessionToken($token_1);
        $user_1 = CurrentUser::get();

        CurrentUser::setSessionToken($token_2);
        $user_2 = CurrentUser::get();

        $this->assertNotSame($user_1->id, $user_2->id);
    }

    public function testGetReturnsNullEvenIfInstanceIsNotResetButSessionIs()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user');
        $this->create('session', [
            'user_id' => $user_id,
            'token' => $token,
        ]);

        CurrentUser::setSessionToken($token);
        $user = CurrentUser::get();
        $this->assertSame($user_id, $user->id);

        unset($_SESSION['current_session_token']);
        $user = CurrentUser::get();
        $this->assertNull($user);
    }

    public function testGetDoesNotResetInstanceIfSessionChanges()
    {
        $faker = \Faker\Factory::create();
        $expired_at = \Minz\Time::fromNow($faker->numberBetween(1, 9000), 'minutes');

        $token_1 = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $token_2 = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id_1 = $this->create('user');
        $user_id_2 = $this->create('user');
        $this->create('session', [
            'user_id' => $user_id_1,
            'token' => $token_1,
        ]);
        $this->create('session', [
            'user_id' => $user_id_2,
            'token' => $token_2,
        ]);

        CurrentUser::setSessionToken($token_1);
        $user = CurrentUser::get();
        $this->assertSame($user_id_1, $user->id);

        $_SESSION['current_session_token'] = $token_2;
        $user = CurrentUser::get();
        $this->assertSame($user_id_1, $user->id);
    }
}
