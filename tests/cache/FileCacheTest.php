<?php

namespace App\cache;

class FileCacheTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FilesystemHelper;

    private static FileCache $cache;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadCache(): void
    {
        self::$cache = new FileCache('test');
    }

    public function testGetItemWithNoHit(): void
    {
        $cache_item = self::$cache->getItem('foo');

        $this->assertSame('foo', $cache_item->getKey());
        $this->assertNull($cache_item->get());
        $this->assertFalse($cache_item->isHit());
    }

    public function testGetItemWithHit(): void
    {
        $expiration = new \DateTimeImmutable('2025-01-01');
        $cache_item = self::$cache->getItem('foo');
        $cache_item->set('bar');
        $cache_item->expiresAt($expiration);
        self::$cache->save($cache_item);

        $cache_item = self::$cache->getItem('foo');

        $this->assertSame('foo', $cache_item->getKey());
        $this->assertSame('bar', $cache_item->get());
        $item_expiration = $cache_item->getExpiration();
        $this->assertNotNull($item_expiration);
        $this->assertSame($expiration->getTimestamp(), $item_expiration->getTimestamp());
        $this->assertTrue($cache_item->isHit());
    }

    public function testHasItemWithNotCachedItem(): void
    {
        $result = self::$cache->hasItem('foo');

        $this->assertFalse($result);
    }

    public function testHasItemWithCachedItem(): void
    {
        $cache_item = self::$cache->getItem('foo');
        self::$cache->save($cache_item);

        $result = self::$cache->hasItem('foo');

        $this->assertTrue($result);
    }

    public function testDeleteItem(): void
    {
        $cache_item = self::$cache->getItem('foo');
        self::$cache->save($cache_item);

        $this->assertTrue(self::$cache->hasItem('foo'));

        $result = self::$cache->deleteItem('foo');

        $this->assertTrue($result);
        $this->assertFalse(self::$cache->hasItem('foo'));
    }

    public function testDeleteItems(): void
    {
        $foo_cache_item = self::$cache->getItem('foo');
        $bar_cache_item = self::$cache->getItem('bar');
        self::$cache->save($foo_cache_item);
        self::$cache->save($bar_cache_item);

        $this->assertTrue(self::$cache->hasItem('foo'));
        $this->assertTrue(self::$cache->hasItem('bar'));

        $result = self::$cache->deleteItems(['foo', 'bar']);

        $this->assertTrue($result);
        $this->assertFalse(self::$cache->hasItem('foo'));
        $this->assertFalse(self::$cache->hasItem('bar'));
    }

    public function testClear(): void
    {
        $cache_item = self::$cache->getItem('foo');
        self::$cache->save($cache_item);

        $this->assertTrue(self::$cache->hasItem('foo'));

        $result = self::$cache->clear();

        $this->assertTrue($result);
        $this->assertFalse(self::$cache->hasItem('foo'));
    }

    public function testClearExpiredItems(): void
    {
        $expired_cache_item = self::$cache->getItem('expired');
        $expired_cache_item->expiresAt(\Minz\Time::ago(1, 'day'));
        $not_expired_cache_item = self::$cache->getItem('not expired');
        $not_expired_cache_item->expiresAt(\Minz\Time::fromNow(1, 'day'));
        self::$cache->save($expired_cache_item);
        self::$cache->save($not_expired_cache_item);

        $this->assertTrue(self::$cache->hasItem('expired'));
        $this->assertTrue(self::$cache->hasItem('not expired'));

        $result = self::$cache->clearExpiredItems();

        $this->assertTrue($result);
        $this->assertFalse(self::$cache->hasItem('expired'));
        $this->assertTrue(self::$cache->hasItem('not expired'));
    }
}
