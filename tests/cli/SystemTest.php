<?php

namespace flusio\cli;

use PHPUnit\Framework\TestCase;

class SystemTest extends TestCase
{
    /**
     * @after
     */
    public function uninstall()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        @unlink($migration_file);
        \Minz\Database::drop();
    }

    public function testSetupWhenFirstTime()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        $request = new \Minz\Request('cli', '/system/setup');

        $this->assertFalse(file_exists($migration_file));

        $application = new Application();
        $response = $application->run($request);

        $this->assertSame(200, $response->code());
        $this->assertSame('The system has been initialized.', $response->render());
        $this->assertTrue(file_exists($migration_file));
    }

    public function testSetupWhenCallingTwice()
    {
        $request = new \Minz\Request('cli', '/system/setup');

        $application = new Application();
        $application->run($request);
        $response = $application->run($request);

        $this->assertSame(200, $response->code());
        $this->assertSame('Your system is already up to date.', $response->render());
    }
}
