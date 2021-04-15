<?php

namespace flusio\auth;

use flusio\models;

class CurrentUserTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
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
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
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
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        CurrentUser::setSessionToken($token);

        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsNullIfTokenHasExpired()
    {
        $expired_at = \Minz\Time::ago($this->fake('numberBetween', 1, 9000), 'minutes');
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
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'invalidated_at' => $this->fake('iso8601'),
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
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');

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
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
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
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');

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

    public function testGetSessionTokenReturnsCurrentSessionToken()
    {
        $token = $this->create('token');
        CurrentUser::setSessionToken($token);

        $result_token = CurrentUser::sessionToken();

        $this->assertSame($token, $result_token);
    }

    public function testGetSessionTokenReturnsNullIfNotSet()
    {
        $token = CurrentUser::sessionToken();

        $this->assertNull($token);
    }

    public function testReloadResetsInstanceAndGetUserFromDatabase()
    {
        $old_username = $this->fakeUnique('username');
        $new_username = $this->fakeUnique('username');
        $expired_at = \Minz\Time::fromNow($this->fake('numberBetween', 1, 9000), 'minutes');
        $token = $this->create('token', [
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $this->create('user', [
            'username' => $old_username,
        ]);
        $this->create('session', [
            'user_id' => $user_id,
            'token' => $token,
        ]);
        CurrentUser::setSessionToken($token);

        $user = CurrentUser::get();
        $user->username = $new_username;

        $user = CurrentUser::reload();
        $this->assertSame($old_username, $user->username);
    }
}
