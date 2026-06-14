<?php

namespace App\cli;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

/**
 * @phpstan-import-type ResponseGenerator from \Minz\Response
 */
class MigrationsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function uninstall(): void
    {
        $migration_file = \App\Configuration::$data_path . '/migrations_version.txt';
        @unlink($migration_file);
        \Minz\Database::drop();
    }

    #[\PHPUnit\Framework\Attributes\AfterClass]
    public static function recreateDatabase(): void
    {
        \Minz\Database::reset();
        $schema = @file_get_contents(\App\Configuration::$schema_path);
        assert($schema !== false);
        $database = \Minz\Database::get();
        $database->exec($schema);
    }

    public function testAllMigrationsCanBeApplied(): void
    {
        $migrations_version_path = \App\Configuration::$data_path . '/migrations_version.txt';
        $migrations_path = \App\Configuration::$app_path . '/src/migrations';
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
        $migrations_path = \App\Configuration::$app_path . '/src/migrations';
        $migrations_version_path = \App\Configuration::$data_path . '/migrations_version.txt';
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

    public function testSetupUrlStatuses(): void
    {
        self::recreateDatabase();

        $user = UserFactory::create();
        $read_list = $user->readList();
        $read_later_list = $user->bookmarks();
        $dismissed_list = $user->neverList();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_3 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        /** @var \DateTimeImmutable */
        $link_1_read_at = $this->fake('dateTime');
        $read_list->addLinks([$link_1], at: $link_1_read_at);
        /** @var \DateTimeImmutable */
        $link_2_read_later_at = $this->fake('dateTime');
        $read_later_list->addLinks([$link_2], at: $link_2_read_later_at);
        /** @var \DateTimeImmutable */
        $link_3_dismissed_at = $this->fake('dateTime');
        $dismissed_list->addLinks([$link_3], at: $link_3_dismissed_at);

        /** @var ResponseGenerator */
        $response = $this->appRun('CLI', '/migrations/setup-url-statuses');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '3 statuses migrated…');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Finished: 3 statuses migrated');

        $status_link_1 = models\UrlStatus::findBy([
            'user_id' => $user->id,
            'url_hash' => $link_1->url_hash,
        ]);
        $this->assertNotNull($status_link_1);
        $this->assertEquals($link_1_read_at, $status_link_1->created_at);
        $this->assertEquals($link_1_read_at, $status_link_1->read_at);
        $this->assertNull($status_link_1->read_later_at);
        $this->assertNull($status_link_1->dismissed_at);

        $status_link_2 = models\UrlStatus::findBy([
            'user_id' => $user->id,
            'url_hash' => $link_2->url_hash,
        ]);
        $this->assertNotNull($status_link_2);
        $this->assertEquals($link_2_read_later_at, $status_link_2->created_at);
        $this->assertNull($status_link_2->read_at);
        $this->assertEquals($link_2_read_later_at, $status_link_2->read_later_at);
        $this->assertNull($status_link_2->dismissed_at);

        $status_link_3 = models\UrlStatus::findBy([
            'user_id' => $user->id,
            'url_hash' => $link_3->url_hash,
        ]);
        $this->assertNotNull($status_link_3);
        $this->assertEquals($link_3_dismissed_at, $status_link_3->created_at);
        $this->assertNull($status_link_3->read_at);
        $this->assertNull($status_link_3->read_later_at);
        $this->assertEquals($link_3_dismissed_at, $status_link_3->dismissed_at);
    }

    public function testSetupUrlStatusesWithMultipleStatusInSameBatch(): void
    {
        self::recreateDatabase();

        $user = UserFactory::create();
        $read_list = $user->readList();
        $read_later_list = $user->bookmarks();
        $dismissed_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_read_at = \Minz\Time::ago(30, 'days');
        $link_read_later_at = \Minz\Time::ago(20, 'days');
        $link_dismissed_at = \Minz\Time::ago(10, 'days');
        $read_list->addLinks([$link], at: $link_read_at);
        $read_later_list->addLinks([$link], at: $link_read_later_at);
        $dismissed_list->addLinks([$link], at: $link_dismissed_at);

        /** @var ResponseGenerator */
        $response = $this->appRun('CLI', '/migrations/setup-url-statuses');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '3 statuses migrated…');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Finished: 3 statuses migrated');

        $status_link = models\UrlStatus::findBy([
            'user_id' => $user->id,
            'url_hash' => $link->url_hash,
        ]);
        $this->assertNotNull($status_link);
        $this->assertSame(
            $link_read_at->getTimestamp(),
            $status_link->created_at->getTimestamp()
        );
        $this->assertSame(
            $link_read_at->getTimestamp(),
            $status_link->read_at?->getTimestamp()
        );
        $this->assertSame(
            $link_read_later_at->getTimestamp(),
            $status_link->read_later_at?->getTimestamp()
        );
        $this->assertSame(
            $link_dismissed_at->getTimestamp(),
            $status_link->dismissed_at?->getTimestamp()
        );
    }

    public function testSetupUrlStatusesWithMultipleStatusInDifferentBatches(): void
    {
        self::recreateDatabase();

        $user = UserFactory::create();
        $read_list = $user->readList();
        $read_later_list = $user->bookmarks();
        $dismissed_list = $user->neverList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link_read_at = \Minz\Time::ago(30, 'days');
        $link_read_later_at = \Minz\Time::ago(20, 'days');
        $link_dismissed_at = \Minz\Time::ago(10, 'days');
        $read_list->addLinks([$link], at: $link_read_at);
        $read_later_list->addLinks([$link], at: $link_read_later_at);
        $dismissed_list->addLinks([$link], at: $link_dismissed_at);

        /** @var ResponseGenerator */
        $response = $this->appRun('CLI', '/migrations/setup-url-statuses', [
            'batch-size' => 1,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '1 statuses migrated…');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '2 statuses migrated…');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, '3 statuses migrated…');

        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Finished: 3 statuses migrated');

        $status_link = models\UrlStatus::findBy([
            'user_id' => $user->id,
            'url_hash' => $link->url_hash,
        ]);
        $this->assertNotNull($status_link);
        $this->assertSame(
            $link_read_at->getTimestamp(),
            $status_link->created_at->getTimestamp()
        );
        $this->assertSame(
            $link_read_at->getTimestamp(),
            $status_link->read_at?->getTimestamp()
        );
        $this->assertSame(
            $link_read_later_at->getTimestamp(),
            $status_link->read_later_at?->getTimestamp()
        );
        $this->assertSame(
            $link_dismissed_at->getTimestamp(),
            $status_link->dismissed_at?->getTimestamp()
        );
    }
}
