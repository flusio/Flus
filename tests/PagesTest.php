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

    public function testHomeRedirectsToBookmarksIfConnected()
    {
        $this->login();

        $response = $this->appRun('GET', '/');

        $this->assertResponse($response, 302, '/bookmarks');
    }

    public function testAboutRendersCorrectly()
    {
        $response = $this->appRun('GET', '/about');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'pages/about.phtml');
    }

    public function testDesignRendersCorrectly()
    {
        $response = $this->appRun('GET', '/design');

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'pages/design.phtml');
    }
}
