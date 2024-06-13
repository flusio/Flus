<?php

namespace App\cli;

/**
 * @phpstan-import-type ConfigurationDatabase from \Minz\Configuration
 */
class DatabaseTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    #[\PHPUnit\Framework\Attributes\AfterClass]
    public static function recreateDatabase(): void
    {
        \Minz\Database::reset();
        $schema = @file_get_contents(\Minz\Configuration::$schema_path);

        assert($schema !== false);

        $database = \Minz\Database::get();
        $database->exec($schema);
    }

    public function testStatusCanConnectToTheDatabase(): void
    {
        $response = $this->appRun('CLI', '/database/status');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Database status: OK');
    }

    public function testStatusFailsWithWrongCredentials(): void
    {
        \Minz\Database::drop(); // make sure to reset the singleton instance
        $initial_database_conf = \Minz\Configuration::$database;
        /** @var ConfigurationDatabase */
        $configuration = \Minz\Configuration::$database;
        $configuration['username'] = 'not the username';
        \Minz\Configuration::$database = $configuration;

        $response = $this->appRun('CLI', '/database/status');

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, 'Database status: An error occured during database initialization');

        \Minz\Configuration::$database = $initial_database_conf;
    }
}
