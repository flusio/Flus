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

        $this->assertResponse($response, 200);
        $output = trim($response->render());
        $this->assertTrue(strlen($output) >= 128);
    }

    public function testSetupWhenFirstTime()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';

        $this->assertFalse(file_exists($migration_file));

        $response_generator = $this->appRun('cli', '/system/setup');
        $response = $response_generator->current();

        $this->assertResponse($response, 200, 'The system has been initialized.');
        $this->assertTrue(file_exists($migration_file));
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponse($response, 200, 'Seeds loaded.');
    }

    public function testSetupWhenCallingTwice()
    {
        $response_generator = $this->appRun('cli', '/system/setup');
        $response_generator->current(); // action is not called if generator is not executed

        $response_generator = $this->appRun('cli', '/system/setup');
        $response = $response_generator->current();

        $this->assertResponse($response, 200, 'Your system is already up to date.');
    }

    public function testSetupWithMigrations()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        touch($migration_file);
        \Minz\Database::create();

        $response_generator = $this->appRun('cli', '/system/setup');
        $response = $response_generator->current();

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

        $response_generator = $this->appRun('cli', '/system/setup');
        $response = $response_generator->current();

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

        $response_generator = $this->appRun('cli', '/system/setup');
        $response = $response_generator->current();

        @unlink($failing_migration_path);

        $this->assertResponse($response, 500, 'TheFailingMigrationWithMessage: this test fails :(');
    }

    public function testAllMigrationsCanBeRollback()
    {
        $migrations_path = \Minz\Configuration::$app_path . '/src/migrations';
        $migrations_version_path = \Minz\Configuration::$data_path . '/migrations_version.txt';
        $number_migrations = count(scandir($migrations_path)) - 2;
        \Minz\Database::create();
        $migrator = new \Minz\Migrator($migrations_path);
        $migrator->migrate();
        @file_put_contents($migrations_version_path, $migrator->version());

        $response = $this->appRun('cli', '/system/rollback', [
            'steps' => $number_migrations,
        ]);

        $this->assertResponse($response, 200);
    }

    public function testRollbackWithAFailingRollback()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        touch($migration_file);
        $failing_migration_path = \Minz\Configuration::$app_path . '/src/migrations/TheFailingRollbackWithFalse.php';
        $failing_migration_content = <<<'PHP'
            <?php

            namespace flusio\migrations;

            class TheFailingRollbackWithFalse
            {
                public function migrate()
                {
                    return true;
                }

                public function rollback()
                {
                    return false;
                }
            }
            PHP;
        file_put_contents($failing_migration_path, $failing_migration_content);
        file_put_contents($migration_file, 'TheFailingRollbackWithFalse');

        $response = $this->appRun('cli', '/system/rollback');

        @unlink($failing_migration_path);

        $this->assertResponse($response, 500, 'TheFailingRollbackWithFalse: KO');
    }
}
