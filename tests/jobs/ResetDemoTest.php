<?php

namespace flusio\jobs;

use flusio\models;

class ResetDemoTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;

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
