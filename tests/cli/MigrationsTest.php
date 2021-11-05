<?php

namespace flusio\cli;

class MigrationsTest extends \PHPUnit\Framework\TestCase
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

    public function testAllMigrationsCanBeApplied()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        $migrations_path = \Minz\Configuration::$app_path . '/src/migrations';
        touch($migration_file);
        \Minz\Database::create();
        $migrator = new \Minz\Migrator($migrations_path);
        $last_migration_version = $migrator->lastVersion();
        $expected_output = [];
        foreach ($migrator->migrations() as $version => $migration) {
            $expected_output[] = "{$version}: OK";
        }
        $expected_output = implode("\n", $expected_output);

        $response = $this->appRun('cli', '/migrations/apply');

        $this->assertResponseCode($response, 200);
        $current_migration_version = @file_get_contents($migration_file);
        $this->assertSame($last_migration_version, $current_migration_version);
        $this->assertResponseEquals($response, $expected_output);
    }

    public function testApplySucceedsIfUpToDate()
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        $migrations_path = \Minz\Configuration::$app_path . '/src/migrations';
        $migrator = new \Minz\Migrator($migrations_path);
        $last_migration_version = $migrator->lastVersion();
        file_put_contents($migration_file, $last_migration_version);

        $response = $this->appRun('cli', '/migrations/apply');

        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Your system is already up to date.');
    }

    public function testApplyWithAFailingMigrationReturningFalse()
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

        $response = $this->appRun('cli', '/migrations/apply');

        @unlink($failing_migration_path);

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, 'TheFailingMigrationWithFalse: KO');
    }

    public function testApplyWithAFailingMigrationReturningAMessage()
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

        $response = $this->appRun('cli', '/migrations/apply');

        @unlink($failing_migration_path);

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, 'TheFailingMigrationWithMessage: this test fails :(');
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

        $response = $this->appRun('cli', '/migrations/rollback', [
            'steps' => $number_migrations,
        ]);

        $this->assertResponseCode($response, 200);
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

        $response = $this->appRun('cli', '/migrations/rollback');

        @unlink($failing_migration_path);

        $this->assertResponseCode($response, 500);
        $this->assertResponseContains($response, 'TheFailingRollbackWithFalse: KO');
    }
}
