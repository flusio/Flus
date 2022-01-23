<?php

namespace tests;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait MockHttpHelper
{
    /**
     * Clear mocks after each test if it has been enabled.
     *
     * @after
     */
    public function clearMockHttp()
    {
        if (\SpiderBits\Http::$mock_host) {
            $http = new \SpiderBits\Http();
            $http->post('/', [
                'action' => 'clear',
            ]);

            \SpiderBits\Http::$mock_host = '';
        }
    }

    /**
     * Indicate to the mock server that it must answer with the given HTTP
     * response when url is called.
     *
     * @param string $url
     * @param string $raw_response
     */
    public function mockHttpWithResponse($url, $raw_response)
    {
        \SpiderBits\Http::$mock_host = \Minz\Configuration::$application['mock_host'];

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
     *
     * @param string $url
     * @param string $fixture_path
     */
    public function mockHttpWithFixture($url, $fixture_name)
    {
        $app_path = \Minz\Configuration::$app_path;
        $fixtures_path = $app_path . '/tests/fixtures';
        $fixture_pathname = "{$fixtures_path}/{$fixture_name}";
        $raw_response = file_get_contents($fixture_pathname);

        $this->mockHttpWithResponse($url, $raw_response);
    }

    /**
     * Indicate to the mock server that it must answer by serving the given
     * file when url is called.
     *
     * @param string $url
     * @param string $filename
     */
    public function mockHttpWithFile($url, $filename)
    {
        \SpiderBits\Http::$mock_host = \Minz\Configuration::$application['mock_host'];

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
     *
     * @param string $url
     */
    public function mockHttpWithEcho($url)
    {
        \SpiderBits\Http::$mock_host = \Minz\Configuration::$application['mock_host'];

        $http = new \SpiderBits\Http();
        $http->post('/', [
            'url' => urlencode($url),
            'action' => 'mock',
            'mock' => 'echo',
        ]);
    }
}
