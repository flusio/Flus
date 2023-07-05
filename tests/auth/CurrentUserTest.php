<?php

namespace flusio\auth;

use flusio\models;
use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class CurrentUserTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;

    /**
     * @before
     */
    public function resetCurrentUser(): void
    {
        CurrentUser::reset();
    }

    public function testSetSessionTokenSetsTheTokenInSession(): void
    {
        $token = TokenFactory::create();

        CurrentUser::setSessionToken($token->token);

        $this->assertSame($token->token, $_SESSION['current_session_token']);
    }

    public function testGetReturnsTheUser(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create();
        SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
        ]);
        CurrentUser::setSessionToken($token->token);

        $current_user = CurrentUser::get();

        $this->assertNotNull($current_user);
        $this->assertSame($user->id, $current_user->id);
    }

    public function testGetReturnsNullIfSessionTokenIsNotInSession(): void
    {
        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsNullIfTheUserDoesNotExist(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        CurrentUser::setSessionToken($token->token);

        $user = CurrentUser::get();

        $this->assertNull($user);
    }

    public function testGetReturnsNullIfTokenHasExpired(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::ago($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create();
        SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
        ]);
        CurrentUser::setSessionToken($token->token);

        $current_user = CurrentUser::get();

        $this->assertNull($current_user);
    }

    public function testGetReturnsNullIfTokenIsInvalidated(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
            'invalidated_at' => \Minz\Time::now(),
        ]);
        $user = UserFactory::create();
        SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
        ]);
        CurrentUser::setSessionToken($token->token);

        $current_user = CurrentUser::get();

        $this->assertNull($current_user);
    }

    public function testGetReturnsTheCorrectUserAfterChangingTheCurrentUser(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');

        $token_1 = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $token_2 = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user_1 = UserFactory::create();
        $user_2 = UserFactory::create();
        SessionFactory::create([
            'user_id' => $user_1->id,
            'token' => $token_1->token,
        ]);
        SessionFactory::create([
            'user_id' => $user_2->id,
            'token' => $token_2->token,
        ]);

        CurrentUser::setSessionToken($token_1->token);
        $current_user_1 = CurrentUser::get();
        $this->assertNotNull($current_user_1);

        CurrentUser::setSessionToken($token_2->token);
        $current_user_2 = CurrentUser::get();
        $this->assertNotNull($current_user_2);

        $this->assertNotSame($current_user_1->id, $current_user_2->id);
    }

    public function testGetReturnsNullEvenIfInstanceIsNotResetButSessionIs(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create();
        SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
        ]);

        CurrentUser::setSessionToken($token->token);
        $current_user = CurrentUser::get();
        $this->assertNotNull($current_user);
        $this->assertSame($user->id, $current_user->id);

        unset($_SESSION['current_session_token']);
        $current_user = CurrentUser::get();
        $this->assertNull($current_user);
    }

    public function testGetDoesNotResetInstanceIfSessionChanges(): void
    {
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');

        $token_1 = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $token_2 = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user_1 = UserFactory::create();
        $user_2 = UserFactory::create();
        SessionFactory::create([
            'user_id' => $user_1->id,
            'token' => $token_1->token,
        ]);
        SessionFactory::create([
            'user_id' => $user_2->id,
            'token' => $token_2->token,
        ]);

        CurrentUser::setSessionToken($token_1->token);
        $current_user = CurrentUser::get();
        $this->assertNotNull($current_user);
        $this->assertSame($user_1->id, $current_user->id);

        $_SESSION['current_session_token'] = $token_2->token;
        $current_user = CurrentUser::get();
        $this->assertNotNull($current_user);
        $this->assertSame($user_1->id, $current_user->id);
    }

    public function testGetSessionTokenReturnsCurrentSessionToken(): void
    {
        $token = TokenFactory::create();
        CurrentUser::setSessionToken($token->token);

        $result_token = CurrentUser::sessionToken();

        $this->assertSame($token->token, $result_token);
    }

    public function testGetSessionTokenReturnsNullIfNotSet(): void
    {
        $token = CurrentUser::sessionToken();

        $this->assertNull($token);
    }

    public function testReloadResetsInstanceAndGetUserFromDatabase(): void
    {
        /** @var string */
        $old_username = $this->fakeUnique('username');
        /** @var string */
        $new_username = $this->fakeUnique('username');
        /** @var int */
        $minutes = $this->fake('numberBetween', 1, 9000);
        $expired_at = \Minz\Time::fromNow($minutes, 'minutes');
        $token = TokenFactory::create([
            'expired_at' => $expired_at,
        ]);
        $user = UserFactory::create([
            'username' => $old_username,
        ]);
        SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
        ]);
        CurrentUser::setSessionToken($token->token);

        $current_user = CurrentUser::get();
        $this->assertNotNull($current_user);
        $current_user->username = $new_username;

        $current_user = CurrentUser::reload();
        $this->assertNotNull($current_user);
        $this->assertSame($old_username, $current_user->username);
    }
}
