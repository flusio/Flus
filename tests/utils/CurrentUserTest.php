<?php

namespace flusio\utils;

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

    public function testSetSetsTheCurrentUserInSession()
    {
        $user_id = $this->create('user');

        CurrentUser::set($user_id);

        $this->assertSame($user_id, $_SESSION['current_user_id']);
    }

    public function testGetReturnsTheUser()
    {
        $user_id = $this->create('user');
        CurrentUser::set($user_id);

        $user = CurrentUser::get();

        $this->assertSame($user_id, $user->id);
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
        $user_id_1 = $this->create('user');
        $user_id_2 = $this->create('user');

        CurrentUser::set($user_id_1);
        $user_1 = CurrentUser::get();

        CurrentUser::set($user_id_2);
        $user_2 = CurrentUser::get();

        $this->assertNotSame($user_1->id, $user_2->id);
    }

    public function testGetReturnsNullEvenIfInstanceIsNotResetButSessionIs()
    {
        $user_id = $this->create('user');

        CurrentUser::set($user_id);
        $user = CurrentUser::get();
        $this->assertSame($user_id, $user->id);

        unset($_SESSION['current_user_id']);
        $user = CurrentUser::get();
        $this->assertNull($user);
    }

    public function testGetResetsInstanceIfSessionChanges()
    {
        $user_id_1 = $this->create('user');
        $user_id_2 = $this->create('user');

        CurrentUser::set($user_id_1);
        $user = CurrentUser::get();
        $this->assertSame($user_id_1, $user->id);

        $_SESSION['current_user_id'] = $user_id_2;
        $user = CurrentUser::get();
        $this->assertSame($user_id_2, $user->id);
    }
}
