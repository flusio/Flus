<?php

namespace flusio;

class PagesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testHomeRendersCorrectly()
    {
        $response = $this->appRun('GET', '/');

        $this->assertResponse($response, 200, 'Hello World!');
        $this->assertPointer($response, 'pages/home.phtml');
    }

    public function testAboutRendersCorrectly()
    {
        $response = $this->appRun('GET', '/about');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'pages/about.phtml');
    }
}
