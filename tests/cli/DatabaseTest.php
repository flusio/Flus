<?php

namespace flusio\cli;

use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function testStatusCanConnectToTheDatabase()
    {
        $request = new \Minz\Request('cli', '/database/status');

        $application = new Application();
        $response = $application->run($request);

        $this->assertSame(200, $response->code());
        $this->assertSame('Database status: OK', $response->render());
    }

    public function testStatusFailsWithWrongCredentials()
    {
        \Minz\Database::drop(); // make sure to reset the singleton instance
        $initial_database_conf = \Minz\Configuration::$database;
        \Minz\Configuration::$database['username'] = 'not the username';

        $request = new \Minz\Request('cli', '/database/status');

        $application = new Application();
        $response = $application->run($request);

        $this->assertSame(500, $response->code());
        $this->assertStringContainsString(
            'Database status: An error occured during database initialization',
            $response->render()
        );

        \Minz\Configuration::$database = $initial_database_conf;
    }
}
