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
     * @before
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

        $this->assertFalse(file_exists($migration_file));

        $response = $this->appRun('cli', '/system/setup');

        $this->assertResponse($response, 200, 'The system has been initialized.');
        $this->assertTrue(file_exists($migration_file));
    }

    public function testSetupWhenCallingTwice()
    {
        $this->appRun('cli', '/system/setup');
        $response = $this->appRun('cli', '/system/setup');

        $this->assertResponse($response, 200, 'Your system is already up to date.');
    }

    public function testSetupWithMigrations()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        touch($migration_file);
        \Minz\Database::create();

        $response = $this->appRun('cli', '/system/setup');

        $this->assertResponse($response, 200);
    }

    public function testSetupWithAFailingMigrationReturningFalse()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        touch($migration_file);
        $failing_migration_path = \Minz\Configuration::$app_path . '/src/migrations/TheFailingMigrationWithFalse.php';
        $failing_migration_content = <<<'PHP'
            <?php

            namespace flusio\migrations;

            class TheFailingMigrationWithFalse
            {
                public function migrate()
                {
                    return false;
                }
            }
            PHP;
        file_put_contents($failing_migration_path, $failing_migration_content);

        \Minz\Database::create();

        $response = $this->appRun('cli', '/system/setup');

        @unlink($failing_migration_path);

        $this->assertResponse($response, 500, 'TheFailingMigrationWithFalse: KO');
    }

    public function testSetupWithAFailingMigrationReturningAMessage()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        touch($migration_file);
        $failing_migration_path = \Minz\Configuration::$app_path . '/src/migrations/TheFailingMigrationWithMessage.php';
        $failing_migration_content = <<<'PHP'
            <?php

            namespace flusio\migrations;

            class TheFailingMigrationWithMessage
            {
                public function migrate()
                {
                    throw new \Exception('this test fails :(');
                }
            }
            PHP;
        file_put_contents($failing_migration_path, $failing_migration_content);

        \Minz\Database::create();

        $response = $this->appRun('cli', '/system/setup');

        @unlink($failing_migration_path);

        $this->assertResponse($response, 500, 'TheFailingMigrationWithMessage: this test fails :(');
    }
}
