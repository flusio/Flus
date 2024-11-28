<?php

namespace SpiderBits;

class CacheTest extends \PHPUnit\Framework\TestCase
{
    private static string $cache_path;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function setCachePath(): void
    {
        $cache_path = \App\Configuration::$application['cache_path'];
        self::$cache_path = $cache_path;
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function emptyCachePath(): void
    {
        $files = glob(self::$cache_path . '/*');

        assert($files !== false);

        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testConstructFailsIfPathDoesNotExist(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The cache path does not exist');

        new Cache('do not exist');
    }

    public function testConstructFailsIfPathIsNotDirectory(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The cache path is not a directory');

        new Cache(__FILE__);
    }

    public function testSave(): void
    {
        $cache = new Cache(self::$cache_path);

        $result = $cache->save('foo', 'bar');

        $this->assertTrue($result);
        $this->assertTrue(file_exists(self::$cache_path . '/foo'));
    }

    public function testGet(): void
    {
        $cache = new Cache(self::$cache_path);
        $cache->save('foo', 'bar');

        $text = $cache->get('foo');

        $this->assertSame('bar', $text);
    }

    public function testGetReturnsNullIfExpired(): void
    {
        $cache = new Cache(self::$cache_path);
        $cache->save('foo', 'bar');

        $text = $cache->get('foo', -1);

        $this->assertNull($text);
    }

    public function testGetReturnsNullIfFileDoesNotExist(): void
    {
        $cache = new Cache(self::$cache_path);

        $text = $cache->get('foo');

        $this->assertNull($text);
    }

    public function testGetReturnsNullIfFileCannotBeRead(): void
    {
        $cache = new Cache(self::$cache_path);
        $cache->save('foo', 'bar');
        chmod(self::$cache_path . '/foo', 0);

        $text = $cache->get('foo');

        $this->assertNull($text);
    }

    public function testGetReturnsNullIfFileCannotBeDecoded(): void
    {
        $cache = new Cache(self::$cache_path);
        file_put_contents(self::$cache_path . '/foo', 'not an encoded string');

        $text = $cache->get('foo');

        $this->assertNull($text);
    }

    public function testRemove(): void
    {
        $cache = new Cache(self::$cache_path);
        $cache->save('foo', 'bar');

        $this->assertTrue(file_exists(self::$cache_path . '/foo'));

        $result = $cache->remove('foo');

        $this->assertTrue($result);
        $this->assertFalse(file_exists(self::$cache_path . '/foo'));
    }

    public function testRemoveReturnsTrueIfFileDoesNotExist(): void
    {
        $cache = new Cache(self::$cache_path);

        $this->assertFalse(file_exists(self::$cache_path . '/foo'));

        $result = $cache->remove('foo');

        $this->assertTrue($result);
        $this->assertFalse(file_exists(self::$cache_path . '/foo'));
    }

    public function testRemoveReturnsFalseIfFileCannotBeRemoved(): void
    {
        $cache = new Cache(self::$cache_path);
        $cache->save('foo', 'bar');
        chmod(self::$cache_path, 0500);

        $this->assertTrue(file_exists(self::$cache_path . '/foo'));

        $result = $cache->remove('foo');

        chmod(self::$cache_path, 0755);
        $this->assertFalse($result);
        $this->assertTrue(file_exists(self::$cache_path . '/foo'));
    }

    public function testHash(): void
    {
        $string = 'Hello World!';

        $hash = Cache::hash($string);

        $this->assertSame('7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069', $hash);
    }

    public function testClean(): void
    {
        $cache = new Cache(self::$cache_path);
        $filepath = self::$cache_path . '/foo';
        $validity_interval = 7 * 24 * 60 * 60;
        $modification_time = time() - $validity_interval;
        touch($filepath, $modification_time);

        $this->assertTrue(file_exists($filepath));

        $cache->clean($validity_interval);

        $this->assertFalse(file_exists($filepath));
    }

    public function testCleanKeepsFilesWithinValidityInterval(): void
    {
        $cache = new Cache(self::$cache_path);
        $filepath = self::$cache_path . '/foo';
        $validity_interval = 7 * 24 * 60 * 60;
        $modification_time = time() - $validity_interval + 1;
        touch($filepath, $modification_time);

        $this->assertTrue(file_exists($filepath));

        $cache->clean($validity_interval);

        $this->assertTrue(file_exists($filepath));
    }
}
