<?php

namespace App\cli;

class MigrationsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function uninstall(): void
    {
        $migration_file = \Minz\Configuration::$data_path . '/migrations_version.txt';
        @unlink($migration_file);
        \Minz\Database::drop();
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

    public function testAllMigrationsCanBeApplied(): void
    {
        $migrations_version_path = \Minz\Configuration::$data_path . '/migrations_version.txt';
        $migrations_path = \Minz\Configuration::$app_path . '/src/migrations';
        touch($migrations_version_path);
        \Minz\Database::create();
        $migrator = new \Minz\Migration\Migrator($migrations_path);
        $last_migration_version = $migrator->lastVersion();
        $expected_output = [];
        foreach ($migrator->migrations() as $version => $migration) {
            $expected_output[] = "{$version}: OK";
        }
        $expected_output = implode("\n", $expected_output);

        $response = $this->appRun('CLI', '/migrations/setup');

        $this->assertResponseCode($response, 200);
        $current_migration_version = @file_get_contents($migrations_version_path);
        $this->assertSame($last_migration_version, $current_migration_version);
        $this->assertResponseEquals($response, $expected_output);
    }

    public function testAllMigrationsCanRollback(): void
    {
        $migrations_path = \Minz\Configuration::$app_path . '/src/migrations';
        $migrations_version_path = \Minz\Configuration::$data_path . '/migrations_version.txt';
        $migrations_files = scandir($migrations_path);
        $this->assertNotFalse($migrations_files);
        $number_migrations = count($migrations_files) - 2;
        \Minz\Database::create();
        $migrator = new \Minz\Migration\Migrator($migrations_path);
        $migrator->migrate();
        @file_put_contents($migrations_version_path, $migrator->version());
        $expected_output = [];
        foreach ($migrator->migrations(reverse: true) as $version => $migration) {
            $expected_output[] = "{$version}: OK";
        }
        $expected_output = implode("\n", $expected_output);

        $response = $this->appRun('CLI', '/migrations/rollback', [
            'steps' => $number_migrations,
        ]);

        $this->assertResponseCode($response, 200);
        $current_migration_version = @file_get_contents($migrations_version_path);
        $this->assertSame('', $current_migration_version);
        $this->assertResponseEquals($response, $expected_output);
    }
}
