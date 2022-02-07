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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Usage: php cli COMMAND [--OPTION=VALUE]...');
    }
}
