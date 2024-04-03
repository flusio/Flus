<?php

namespace App\controllers;

class AssetsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowReturnsTheAsset(): void
    {
        $response = $this->appRun('GET', '/src/assets/javascripts/application.js');

        $this->assertResponseCode($response, 200);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'text/javascript',
        ]);
    }

    public function testShowReturns404IfFileDoesntExist(): void
    {
        $response = $this->appRun('GET', '/src/assets/dont_exist.js');

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'This file doesn’t exist.');
    }

    public function testShowReturns404IfFileCannotBeAccessed(): void
    {
        $response = $this->appRun('GET', '/src/assets/../Application.php');

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'You’ll not get this file!');
    }
}
