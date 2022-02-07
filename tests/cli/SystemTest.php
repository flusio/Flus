<?php

namespace flusio\cli;

class SystemTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @before
     */
    public function uninstall()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        @unlink($migration_file);
        \Minz\Database::drop();
    }

    public function testShow()
    {
        // We need to initialize the DB manually because of the uninstall hook
        // (used for the other tests)
        \Minz\Database::reset();
        $database = \Minz\Database::get();
        $schema = @file_get_contents(\Minz\Configuration::$schema_path);
        $database->exec($schema);

        $response = $this->appRun('cli', '/system');

        $this->assertResponseCode($response, 200);
    }

    public function testSecret()
    {
        $response = $this->appRun('cli', '/system/secret');

        $this->assertResponseCode($response, 200);
        $output = trim($response->render());
        $this->assertTrue(strlen($output) >= 128);
    }

    public function testSetupWhenFirstTime()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';

        $this->assertFalse(file_exists($migration_file));

        $response_generator = $this->appRun('cli', '/system/setup');
        $response = $response_generator->current();

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'The system has been initialized.');
        $this->assertTrue(file_exists($migration_file));
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Seeds loaded.');
    }

    public function testSetupWhenCallingTwice()
    {
        $response_generator = $this->appRun('cli', '/system/setup');
        $response_generator->current(); // action is not called if generator is not executed

        $response_generator = $this->appRun('cli', '/system/setup');
        $response = $response_generator->current();

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Your system is already up to date.');
    }

    public function testSetupWithMigrations()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        touch($migration_file);
        \Minz\Database::create();

        $response_generator = $this->appRun('cli', '/system/setup');
        $response = $response_generator->current();

        $this->assertResponseCode($response, 200);
    }
}
