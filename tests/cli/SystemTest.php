<?php

namespace App\cli;

class SystemTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    public function testShow(): void
    {
        $response = $this->appRun('CLI', '/system');

        $this->assertResponseCode($response, 200);
    }

    public function testStats(): void
    {
        $response = $this->appRun('CLI', '/system/stats');

        $this->assertResponseCode($response, 200);
    }

    public function testSecret(): void
    {
        $response = $this->appRun('CLI', '/system/secret');

        $this->assertResponseCode($response, 200);
        $this->assertInstanceOf(\Minz\Response::class, $response);
        $output = trim($response->render());
        $this->assertTrue(strlen($output) >= 128);
    }
}
