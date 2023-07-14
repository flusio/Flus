<?php

namespace flusio\cli;

class ApplicationTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testRunDoesntFail()
    {
        $request = new \Minz\Request('CLI', '/');

        $application = new Application();
        $response = $application->run($request);

        $this->assertResponseCode($response, 200);
    }
}
