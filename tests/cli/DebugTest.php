<?php

namespace flusio\cli;

class DebugTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    public function testUrlRendersCorrectly()
    {
        $url = $this->fake('url');
        $this->mockHttpWithResponse($url, <<<TEXT
            HTTP/2 200
            Content-type: text/plain

            Hello World!
            TEXT
        );

        $response = $this->appRun('cli', '/debug/url', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContainsIgnoringCase($response, 'Content-type: text/plain');
        $this->assertResponseContains($response, 'Hello World!');
    }
}
