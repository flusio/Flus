<?php

namespace flusio\cli;

class DatabaseTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @afterClass
     */
    public static function recreateDatabase()
    {
        \Minz\Database::reset();
        $schema = @file_get_contents(\Minz\Configuration::$schema_path);
        $database = \Minz\Database::get();
        $database->exec($schema);
    }

    public function testStatusCanConnectToTheDatabase()
    {
        $response = $this->appRun('cli', '/database/status');

        $this->assertResponse($response, 200, 'Database status: OK');
    }

    public function testStatusFailsWithWrongCredentials()
    {
        \Minz\Database::drop(); // make sure to reset the singleton instance
        $initial_database_conf = \Minz\Configuration::$database;
        \Minz\Configuration::$database['username'] = 'not the username';

        $response = $this->appRun('cli', '/database/status');

        $this->assertResponse($response, 500, 'Database status: An error occured during database initialization');

        \Minz\Configuration::$database = $initial_database_conf;
    }
}
