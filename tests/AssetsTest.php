<?php

namespace flusio;

use Minz\Tests\IntegrationTestCase;

class AssetsTest extends IntegrationTestCase
{
    public function testShowReturnsTheAsset()
    {
        $request = new \Minz\Request('get', '/src/assets/javascripts/application.js');

        $response = self::$application->run($request);

        $this->assertResponse($response, 200, null, [
            'Content-Type' => 'text/javascript',
        ]);
    }

    public function testShowReturns404IfFileDoesntExist()
    {
        $request = new \Minz\Request('get', '/src/assets/dont_exist.js');

        $response = self::$application->run($request);

        $this->assertResponse($response, 404, 'This file doesn’t exist.');
    }

    public function testShowReturns404IfFileCannotBeAccessed()
    {
        $request = new \Minz\Request('get', '/src/assets/../Application.php');

        $response = self::$application->run($request);

        $this->assertResponse($response, 404, 'You’ll not get this file!');
    }
}
