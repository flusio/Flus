<?php

namespace App\services;

use tests\factories\LockFactory;

class LockTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

    public function testAcquire(): void
    {
        $this->freeze();

        $lock = Lock::acquire('lock key');

        $this->assertNotNull($lock);
        $this->assertSame('lock key', $lock->key);
        $this->assertSame(
            \Minz\Time::now()->getTimestamp(),
            $lock->created_at->getTimestamp()
        );
        $this->assertSame(
            \Minz\Time::fromNow(1, 'hour')->getTimestamp(),
            $lock->expired_at->getTimestamp()
        );
    }

    public function testAcquireWithExpiredAt(): void
    {
        $this->freeze();
        $expired_at = \Minz\Time::fromNow(2, 'hours');

        $lock = Lock::acquire('lock key', $expired_at);

        $this->assertNotNull($lock);
        $this->assertSame('lock key', $lock->key);
        $this->assertSame(
            \Minz\Time::now()->getTimestamp(),
            $lock->created_at->getTimestamp()
        );
        $this->assertSame(
            \Minz\Time::fromNow(2, 'hours')->getTimestamp(),
            $lock->expired_at->getTimestamp()
        );
    }

    public function testAcquireWithExistingLock(): void
    {
        $this->freeze();
        $existing_lock = Lock::acquire('lock key');

        $lock = Lock::acquire('lock key');

        $this->assertNull($lock);
    }

    public function testAcquireWithExpiredLock(): void
    {
        $this->freeze();
        $existing_lock = Lock::acquire('lock key', \Minz\Time::ago(1, 'hour'));

        $lock = Lock::acquire('lock key');

        $this->assertNotNull($lock);
        $this->assertSame('lock key', $lock->key);
        $this->assertSame(
            \Minz\Time::now()->getTimestamp(),
            $lock->created_at->getTimestamp()
        );
        $this->assertSame(
            \Minz\Time::fromNow(1, 'hour')->getTimestamp(),
            $lock->expired_at->getTimestamp()
        );
    }

    public function testRelease(): void
    {
        $this->freeze();
        $lock = Lock::acquire('lock key');
        $this->assertNotNull($lock);

        $lock->release();

        $this->assertFalse(Lock::exists($lock->key));
    }
}
