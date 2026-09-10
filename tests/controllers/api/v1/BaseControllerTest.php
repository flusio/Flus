<?php

namespace App\controllers\api\v1;

use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

class BaseControllerTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\ApiHelper;

    public function testAuthenticateUserRenewsSession(): void
    {
        $this->freeze();
        $token = TokenFactory::create([
            'expired_at' => \Minz\Time::fromNow(3, 'days'),
        ]);
        $user = UserFactory::create();
        SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
            'scope' => 'api',
            'created_at' => \Minz\Time::ago(10, 'days'),
        ]);

        $response = $this->apiRun('GET', '/api/v1/journal', headers: [
            'Authorization' => "Bearer {$token->token}",
        ]);

        $this->assertResponseCode($response, 200);
        $token = $token->reload();
        $this->assertSame(
            \Minz\Time::fromNow(1, 'month')->getTimestamp(),
            $token->expired_at->getTimestamp(),
        );
    }

    public function testAuthenticateUserRefreshesLastActivity(): void
    {
        $last_activity = new \DateTimeImmutable('2024-11-01');
        $current_datetime = new \DateTimeImmutable('2024-11-30 12:42:42');
        $current_date = new \DateTimeImmutable('2024-11-30 00:00:00');
        $this->freeze($current_datetime);
        $token = TokenFactory::create([
            'expired_at' => \Minz\Time::fromNow(3, 'days'),
        ]);
        $user = UserFactory::create([
            'last_activity_at' => $last_activity,
        ]);
        SessionFactory::create([
            'user_id' => $user->id,
            'token' => $token->token,
            'scope' => 'api',
        ]);

        $response = $this->apiRun('GET', '/api/v1/journal', headers: [
            'Authorization' => "Bearer {$token->token}",
        ]);

        $this->assertResponseCode($response, 200);
        $user = $user->reload();
        $this->assertEquals($current_date, $user->last_activity_at);
    }
}
