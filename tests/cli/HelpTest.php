<?php

namespace flusio\cli;

class HelpTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    public function testShowRendersCorrectly()
    {
        $response = $this->appRun('cli', '/');

        $this->assertResponse($response, 200, 'Usage: php cli COMMAND [--OPTION=VALUE]...');
    }
}
