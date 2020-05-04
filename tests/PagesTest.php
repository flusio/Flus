<?php

namespace flusio;

use Minz\Tests\IntegrationTestCase;

class PagesTest extends IntegrationTestCase
{
    public function testHomeRendersCorrectly()
    {
        $request = new \Minz\Request('GET', '/');

        $response = self::$application->run($request);

        $this->assertResponse($response, 200, 'Hello World!');
        $pointer = $response->output()->pointer();
        $this->assertSame('pages/home.phtml', $pointer);
    }
}
