<?php

namespace tests;

use App\http;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HttpHelper
{
    private static http\Cache $http_cache;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadCache(): void
    {
        self::$http_cache = new http\Cache();
    }

    /**
     * Clear mocks after each test if it has been enabled.
     */
    #[\PHPUnit\Framework\Attributes\After]
    public function clearMockHttp(): void
    {
        if (\SpiderBits\Http::$mock_host) {
            $http = new \SpiderBits\Http();
            $http->post('/', [
                'action' => 'clear',
            ]);

            \SpiderBits\Http::$mock_host = '';
        }
    }

    #[\PHPUnit\Framework\Attributes\After]
    public function clearHttpCache(): void
    {
        self::$http_cache->clear();
    }

    /**
     * Cache the given "raw" HTTP response in the HTTP cache.
     */
    public function cacheHttpResponse(string $url, string $raw_response): void
    {
        $http_response = \SpiderBits\Response::fromText($raw_response);
        self::$http_cache->saveResponse($url, $http_response);
    }

    /**
     * Indicate to the mock server that it must answer with the given HTTP
     * response when url is called.
     */
    public function mockHttpWithResponse(string $url, string $raw_response): void
    {
        $mock_host = \App\Configuration::$application['mock_host'] ?? '';
        \SpiderBits\Http::$mock_host = $mock_host;

        $http = new \SpiderBits\Http();
        $http->post('/', [
            'url' => urlencode($url),
            'action' => 'mock',
            'mock' => $raw_response,
        ]);
    }

    /**
     * Indicate to the mock server that it must answer with the given fixture
     * file when url is called.
     */
    public function mockHttpWithFixture(string $url, string $fixture_name): void
    {
        $app_path = \App\Configuration::$app_path;
        $fixtures_path = $app_path . '/tests/fixtures';
        $fixture_pathname = "{$fixtures_path}/{$fixture_name}";
        $raw_response = @file_get_contents($fixture_pathname);

        assert($raw_response !== false);

        $this->mockHttpWithResponse($url, $raw_response);
    }

    /**
     * Indicate to the mock server that it must answer by serving the given
     * file when url is called.
     */
    public function mockHttpWithFile(string $url, string $filename): void
    {
        $mock_host = \App\Configuration::$application['mock_host'] ?? '';
        \SpiderBits\Http::$mock_host = $mock_host;

        $http = new \SpiderBits\Http();
        $http->post('/', [
            'url' => urlencode($url),
            'action' => 'mock',
            'mock' => $filename,
        ]);
    }

    /**
     * Indicate to the mock server that it must answer by echoing the request
     * when url is called.
     */
    public function mockHttpWithEcho(string $url): void
    {
        $mock_host = \App\Configuration::$application['mock_host'] ?? '';
        \SpiderBits\Http::$mock_host = $mock_host;

        $http = new \SpiderBits\Http();
        $http->post('/', [
            'url' => urlencode($url),
            'action' => 'mock',
            'mock' => 'echo',
        ]);
    }
}
