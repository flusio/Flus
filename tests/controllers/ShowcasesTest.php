<?php

namespace flusio\controllers;

class ShowcasesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectlyForNavigation()
    {
        $response = $this->appRun('GET', '/showcases/navigation');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'showcases/show_navigation.phtml');
        $this->assertResponseContains($response, 'A new navigation is available');
    }

    public function testShowRendersCorrectlyForLink()
    {
        $response = $this->appRun('GET', '/showcases/link');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'showcases/show_link.phtml');
        $this->assertResponseContains($response, 'Links have been improved');
    }

    public function testShowFailsIfIdDoesNotExist()
    {
        $response = $this->appRun('GET', '/showcases/does-not-exist');

        $this->assertResponseCode($response, 404);
    }
}
