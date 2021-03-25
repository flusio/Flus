<?php

namespace flusio\jobs\scheduled;

use flusio\models;

class ResetDemoTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

    public function testQueue()
    {
        $reset_demo_job = new ResetDemo();

        $this->assertSame('default', $reset_demo_job->queue);
    }

    public function testSchedule()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);

        $reset_demo_job = new ResetDemo();

        $expected_perform_at = \Minz\Time::relative('tomorrow 2:00');
        $this->assertSame(
            $expected_perform_at->getTimestamp(),
            $reset_demo_job->perform_at->getTimestamp()
        );
        $this->assertSame('+1 day', $reset_demo_job->frequency);
    }

    public function testPerform()
    {
        $reset_demo_job = new ResetDemo();
        $token = $this->create('token');
        $user_id = $this->create('user', [
            'validation_token' => $token,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);

        $reset_demo_job->perform();

        $this->assertSame(1, models\User::count());
        $this->assertSame(1, models\Collection::count());
        $this->assertSame(0, models\Token::count());
        $this->assertSame(0, models\Link::count());
        $user = models\User::take();
        $collection = models\Collection::take();
        $this->assertNotSame($user_id, $user->id);
        $this->assertNotSame($collection_id, $collection->id);
        $this->assertSame('demo@flus.io', $user->email);
        $this->assertTrue($user->verifyPassword('demo'));
        $this->assertSame($user->id, $collection->user_id);
        $this->assertSame('bookmarks', $collection->type);
    }
}
