<?php

namespace flusio\utils;

use PHPUnit\Framework\TestCase;

class CurrentUserTest extends TestCase
{
    /**
     * @before
     */
    public function reset()
    {
        CurrentUser::reset();
        \Minz\Database::reset();
        $schema = @file_get_contents(\Minz\Configuration::$schema_path);
        $database = \Minz\Database::get();
        $database->exec($schema);
    }

    public function testSetSetsTheCurrentUserInSession()
    {
        $user_factory = new \Minz\Tests\DatabaseFactory('users');
        $user_id = $user_factory->create();

        CurrentUser::set($user_id);

        $this->assertSame($user_id, $_SESSION['current_user_id']);
    }

    public function testGetReturnsTheUser()
    {
        $user_factory = new \Minz\Tests\DatabaseFactory('users');
        $user_id = $user_factory->create();
        CurrentUser::set($user_id);

        $user = CurrentUser::get();

        $this->assertSame($user_id, $user->id);
    }

    public function testGetStoresTheUserInstance()
    {
        $user_factory = new \Minz\Tests\DatabaseFactory('users');
        $user_id_1 = $user_factory->create();
        $user_id_2 = $user_factory->create();
        CurrentUser::set($user_id_1);

        $user_first_call = CurrentUser::get();
        // simulates the set method without reseting the instance
        $_SESSION['current_user_id'] = $user_id_2;
        $user_second_call = CurrentUser::get();

        $this->assertSame($user_first_call->id, $user_second_call->id);
    }

    public function testGetReturnsNullIfCurrentUserIdIsNotInSession()
    {
        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsNullIfTheUserDoesNotExist()
    {
        CurrentUser::set(42);

        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsTheCorrectUserAfterChangingTheCurrentUser()
    {
        $user_factory = new \Minz\Tests\DatabaseFactory('users');
        $user_id_1 = $user_factory->create();
        $user_id_2 = $user_factory->create();

        CurrentUser::set($user_id_1);
        $user_1 = CurrentUser::get();

        CurrentUser::set($user_id_2);
        $user_2 = CurrentUser::get();

        $this->assertNotSame($user_1->id, $user_2->id);
    }
}
