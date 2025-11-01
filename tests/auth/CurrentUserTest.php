<?php

namespace App\auth;

use App\models;
use Minz\Request;
use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class CurrentUserTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetCurrentUser(): void
    {
        CurrentUser::deleteSession();
    }

    public function testCreateBrowserSessionCreatesASession(): void
    {
        $this->freeze();
        $user = UserFactory::create();
        $request = new Request(
            'GET',
            '/',
            headers: [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:138.0) Gecko/20100101 Firefox/138.0',
            ],
            server: [
                'REMOTE_ADDR' => '127.0.0.1',
            ],
        );

        $session = CurrentUser::createBrowserSession($user, $request);

        $this->assertTrue($session->isPersisted());
        $this->assertSame($user->id, $session->user_id);
        $this->assertSame('Firefox on Linux', $session->name);
        $this->assertSame('127.0.0.XXX', $session->ip);
        $token = $session->token();
        $this->assertEquals(
            \Minz\Time::fromNow(1, 'month')->getTimestamp(),
            $token->expired_at->getTimestamp(),
        );
    }

    public function testCreateBrowserSessionFailsWithSupportUser(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot log in with the support user');

        $user = UserFactory::create([
            'email' => \App\Configuration::$application['support_email']
        ]);

        CurrentUser::createBrowserSession($user);
    }

    public function testAuthenticate(): void
    {
        $user = UserFactory::create();
        $token = TokenFactory::create([
            'expired_at' => \Minz\Time::fromNow(1, 'day'),
        ]);
        $session = SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
            'scope' => 'browser',
        ]);

        CurrentUser::authenticate($token->token, 'browser');

        $current_user = CurrentUser::get();
        $this->assertNotNull($current_user);
        $this->assertSame($user->id, $current_user->id);
        $current_session = CurrentUser::session();
        $this->assertNotNull($current_session);
        $this->assertSame($session->id, $current_session->id);
    }

    public function testAuthenticateDoesNotLogInIfTokenIsExpired(): void
    {
        $user = UserFactory::create();
        $token = TokenFactory::create([
            'expired_at' => \Minz\Time::ago(1, 'day'),
        ]);
        $session = SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
            'scope' => 'browser',
        ]);

        CurrentUser::authenticate($token->token, 'browser');

        $current_user = CurrentUser::get();
        $this->assertNull($current_user);
        $current_session = CurrentUser::session();
        $this->assertNull($current_session);
    }

    public function testAuthenticateDoesNotLogInIfTokenIsInvalidated(): void
    {
        $user = UserFactory::create();
        $token = TokenFactory::create([
            'expired_at' => \Minz\Time::fromNow(1, 'day'),
            'invalidated_at' => \Minz\Time::now(),
        ]);
        $session = SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
            'scope' => 'browser',
        ]);

        CurrentUser::authenticate($token->token, 'browser');

        $current_user = CurrentUser::get();
        $this->assertNull($current_user);
        $current_session = CurrentUser::session();
        $this->assertNull($current_session);
    }

    public function testAuthenticateDoesNotLogInIfScopeDoesNotMatch(): void
    {
        $user = UserFactory::create();
        $token = TokenFactory::create([
            'expired_at' => \Minz\Time::fromNow(1, 'day'),
        ]);
        $session = SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
            'scope' => 'browser',
        ]);

        CurrentUser::authenticate($token->token, 'api');

        $current_user = CurrentUser::get();
        $this->assertNull($current_user);
        $current_session = CurrentUser::session();
        $this->assertNull($current_session);
    }

    public function testAuthenticateFailsWithSupportUser(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot log in with the support user');

        $user = UserFactory::create([
            'email' => \App\Configuration::$application['support_email']
        ]);
        $token = TokenFactory::create([
            'expired_at' => \Minz\Time::fromNow(1, 'day'),
        ]);
        $session = SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
            'scope' => 'browser',
        ]);

        CurrentUser::authenticate($token->token, 'browser');
    }
}
