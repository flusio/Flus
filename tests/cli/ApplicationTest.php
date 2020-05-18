<?php

namespace flusio\cli;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    public function testRunDoesntFail()
    {
        $request = new \Minz\Request('cli', '/');

        $application = new Application();
        $response = $application->run($request);

        $this->assertSame(200, $response->code());
    }
}
