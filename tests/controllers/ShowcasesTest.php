<?php

namespace flusio\controllers;

class ShowcasesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $response = $this->appRun('get', '/showcases/navigation');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'showcases/show_navigation.phtml');
        $this->assertResponseContains($response, 'A new navigation is available');
    }

    public function testShowFailsIfIdDoesNotExist()
    {
        $response = $this->appRun('get', '/showcases/does-not-exist');

        $this->assertResponseCode($response, 404);
    }
}
