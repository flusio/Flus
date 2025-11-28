<?php

namespace App\cli;

use App\http;

class UrlsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;
    use \tests\HttpHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $url = $this->fake('url');
        $this->mockHttpWithResponse($url, <<<TEXT
            HTTP/2 200
            Content-type: text/plain

            Hello World!
            TEXT
        );

        $response = $this->appRun('CLI', '/urls/show', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContainsIgnoringCase($response, 'Content-type: text/plain');
        $this->assertResponseContains($response, 'Hello World!');
    }

    public function testShowWithUserAgent(): void
    {
        /** @var string */
        $url = $this->fake('url');
        $this->mockHttpWithEcho($url);

        $response = $this->appRun('CLI', '/urls/show', [
            'url' => $url,
            'user-agent' => 'FlusBot',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '"HTTP_USER_AGENT":"FlusBot"');
    }

    public function testShowFailsWithInvalidUrl(): void
    {
        $url = 'not an url';

        $response = $this->appRun('CLI', '/urls/show', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, '`not an url` is not a valid URL.');
    }

    public function testUncacheClearsCacheOfGivenUrl(): void
    {
        /** @var string */
        $url = $this->fake('url');
        $this->cacheHttpResponse($url, <<<TEXT
            HTTP/2 200
            Content-type: text/plain

            Hello World!
            TEXT);

        $this->assertTrue(self::$http_cache->hasItem($url));

        $response = $this->appRun('CLI', '/urls/uncache', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertFalse(self::$http_cache->hasItem($url));
        $this->assertResponseEquals($response, "Cache for {$url} has been cleared.");
    }

    public function testUncacheFailsWithInvalidUrl(): void
    {
        $url = 'not an url';

        $response = $this->appRun('CLI', '/urls/uncache', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseEquals($response, "`{$url}` is not a valid URL.");
    }

    public function testUncacheFailsIfCachePathCannotBeWritten(): void
    {
        /** @var string */
        $url = $this->fake('url');
        $this->cacheHttpResponse($url, <<<TEXT
            HTTP/2 200
            Content-type: text/plain

            Hello World!
            TEXT);
        $cache_path = dirname(self::$http_cache->keyToFullpath($url));
        chmod($cache_path, 0500);

        $this->assertTrue(self::$http_cache->hasItem($url));

        $response = $this->appRun('CLI', '/urls/uncache', [
            'url' => $url,
        ]);

        chmod($cache_path, 0775);
        $this->assertResponseCode($response, 500);
        $this->assertTrue(self::$http_cache->hasItem($url));
        $this->assertResponseEquals($response, "Cache for {$url} cannot be cleared.");
    }
}
