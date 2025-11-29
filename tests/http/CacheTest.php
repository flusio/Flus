<?php

namespace App\http;

class CacheTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\TimeHelper;
    use \tests\HttpHelper;

    public function testSaveResponseWithNoHeaders(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertTrue($cache_item->isHit());
        $this->assertEquals(
            \Minz\Time::fromNow(1, 'hour'),
            $cache_item->getExpiration(),
        );
    }

    public function testSaveResponseWithCacheControlMaxAgeHeader(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $max_age = 1 * 60 * 60 * 24;
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain
            Cache-Control: max-age={$max_age}

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertEquals(
            \Minz\Time::fromNow(24, 'hours'),
            $cache_item->getExpiration(),
        );
    }

    public function testSaveResponseWithCacheControlMaxAgeAndAgeHeaders(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $max_age = 1 * 60 * 60 * 24;
        $age = 1 * 60 * 60;
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain
            Cache-Control: max-age={$max_age}
            Age: {$age}

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertEquals(
            \Minz\Time::fromNow(23, 'hours'),
            $cache_item->getExpiration(),
        );
    }

    public function testSaveResponseWithCacheControlNoStoreHeader(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain
            Cache-Control: no-store

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertFalse($cache_item->isHit());
    }

    public function testSaveResponseWithCacheControlNoCacheHeader(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain
            Cache-Control: no-cache

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertFalse($cache_item->isHit());
    }

    public function testSaveResponseWithExpiresHeader(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $expires = \Minz\Time::fromNow(24, 'hours')->format(\DateTimeInterface::RFC7231);
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain
            Expires: {$expires}

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertEquals(
            \Minz\Time::fromNow(24, 'hours'),
            $cache_item->getExpiration(),
        );
    }

    public function testSaveResponseWithInvalidExpiresHeader(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $expires = 'not a date';
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain
            Expires: {$expires}

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertEquals(
            \Minz\Time::fromNow(15, 'minutes'),
            $cache_item->getExpiration(),
        );
    }

    public function testSaveResponseWithDateRetryAfterHeader(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $retry_after = \Minz\Time::fromNow(24, 'hours')->format(\DateTimeInterface::RFC7231);
        $raw_response = <<<TEXT
            HTTP/2 429
            Content-type: text/plain
            Retry-After: {$retry_after}

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertTrue($cache_item->isHit());
        $this->assertEquals(
            \Minz\Time::fromNow(24, 'hours'),
            $cache_item->getExpiration(),
        );
    }

    public function testSaveResponseWithIntRetryAfterHeader(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $retry_after = 1 * 60 * 60 * 24;
        $raw_response = <<<TEXT
            HTTP/2 429
            Content-type: text/plain
            Retry-After: {$retry_after}

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertTrue($cache_item->isHit());
        $this->assertEquals(
            \Minz\Time::fromNow(24, 'hours'),
            $cache_item->getExpiration(),
        );
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('unsupportedStatusCodesProvider')]
    public function testSaveResponseWithUnsupportedStatusCodes(int $code): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $raw_response = <<<TEXT
            HTTP/2 {$code}
            Content-type: text/plain

            Hello World!
            TEXT;

        $this->cacheHttpResponse($url, $raw_response);

        $cache_item = self::$http_cache->getItem($url);
        $this->assertSame($url, $cache_item->getKey());
        $this->assertFalse($cache_item->isHit());
    }

    public function testGetResponse(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain

            Hello World!
            TEXT;
        $this->cacheHttpResponse($url, $raw_response);

        $response = self::$http_cache->getResponse($url);

        $this->assertNotNull($response);
        $this->assertSame('Hello World!', $response->data);
        $this->assertTrue(self::$http_cache->hasItem($url));
    }

    public function testGetResponseWithNoHit(): void
    {
        $this->freeze();
        $url = 'https://example.com';

        $response = self::$http_cache->getResponse($url);

        $this->assertNull($response);
        $this->assertFalse(self::$http_cache->hasItem($url));
    }

    public function testGetResponseWithExpiredItem(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain

            Hello World!
            TEXT;
        $this->cacheHttpResponse($url, $raw_response);
        // Change expiration time manually.
        $cache_item = self::$http_cache->getItem($url);
        $cache_item->expiresAt(\Minz\Time::ago(1, 'hour'));
        self::$http_cache->save($cache_item);

        $response = self::$http_cache->getResponse($url);

        $this->assertNull($response);
        $this->assertFalse(self::$http_cache->hasItem($url));
    }

    public function testGetResponseWithNotStringInCache(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $cache_item = self::$http_cache->getItem($url);
        $cache_item->set(42);
        self::$http_cache->save($cache_item);

        $response = self::$http_cache->getResponse($url);

        $this->assertNull($response);
        $this->assertFalse(self::$http_cache->hasItem($url));
    }

    public function testGetResponseWithNotGzippedResponse(): void
    {
        $this->freeze();
        $url = 'https://example.com';
        $raw_response = <<<TEXT
            HTTP/2 200
            Content-type: text/plain

            Hello World!
            TEXT;
        // Store the raw response directly, not a gzipped version.
        $cache_item = self::$http_cache->getItem($url);
        $cache_item->set($raw_response);
        self::$http_cache->save($cache_item);

        $response = self::$http_cache->getResponse($url);

        $this->assertNull($response);
        $this->assertFalse(self::$http_cache->hasItem($url));
    }

    /**
     * @return array<array{int}>
     */
    public static function unsupportedStatusCodesProvider(): array
    {
        return [
            [100],
            [101],
            [102],
            [103],
            [206],
            [304],
        ];
    }
}
