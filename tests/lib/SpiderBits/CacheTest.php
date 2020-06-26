<?php

namespace SpiderBits;

class CacheTest extends \PHPUnit\Framework\TestCase
{
    private static $cache_path;

    /**
     * @beforeClass
     */
    public static function setCachePath()
    {
        self::$cache_path = \Minz\Configuration::$application['cache_path'];
    }

    /**
     * @before
     */
    public function emptyCachePath()
    {
        $files = glob(self::$cache_path . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testConstructFailsIfPathDoesNotExist()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The cache path does not exist');

        new Cache('do not exist');
    }

    public function testConstructFailsIfPathIsNotDirectory()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('The cache path is not a directory');

        new Cache(__FILE__);
    }

    public function testSave()
    {
        $cache = new Cache(self::$cache_path);

        $result = $cache->save('foo', 'bar');

        $this->assertTrue($result);
        $this->assertTrue(file_exists(self::$cache_path . '/foo'));
    }

    public function testGet()
    {
        $cache = new Cache(self::$cache_path);
        $cache->save('foo', 'bar');

        $text = $cache->get('foo');

        $this->assertSame('bar', $text);
    }

    public function testGetReturnsNullIfExpired()
    {
        $cache = new Cache(self::$cache_path);
        $cache->save('foo', 'bar');

        $text = $cache->get('foo', -1);

        $this->assertNull($text);
    }

    public function testGetReturnsNullIfFileDoesNotExist()
    {
        $cache = new Cache(self::$cache_path);

        $text = $cache->get('foo');

        $this->assertNull($text);
    }

    public function testGetReturnsNullIfFileCannotBeRead()
    {
        $cache = new Cache(self::$cache_path);
        $cache->save('foo', 'bar');
        chmod(self::$cache_path . '/foo', 0);

        $text = $cache->get('foo');

        $this->assertNull($text);
    }

    public function testGetReturnsNullIfFileCannotBeDecoded()
    {
        $cache = new Cache(self::$cache_path);
        file_put_contents(self::$cache_path . '/foo', 'not an encoded string');

        $text = $cache->get('foo');

        $this->assertNull($text);
    }

    public function testHash()
    {
        $string = 'Hello World!';

        $hash = Cache::hash($string);

        $this->assertSame('7f83b1657ff1fc53b92dc18148a1d65dfc2d4b1fa3d677284addd200126d9069', $hash);
    }
}
