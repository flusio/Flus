<?php

namespace flusio;

class PagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testHomeRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('GET', '/');

        $this->assertResponse($response, 302, '/login');
    }

    public function testHomeRedirectsToNewsIfConnected()
    {
        $this->login();

        $response = $this->appRun('GET', '/');

        $this->assertResponse($response, 302, '/news');
    }

    public function testDesignRendersCorrectly()
    {
        $response = $this->appRun('GET', '/design');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'pages/design.phtml');
    }
}
