<?php

namespace App\cli;

use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\NoteFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;
use tests\factories\ViewFactory;

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

    public function testOwnersSetsTheOwnersOfTheContents(): void
    {
        $this->installSchema();
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'owner_id' => $user->owner()->id,
        ]);
        $feed_link = LinkFactory::create([
            'owner_id' => models\SystemOwner::get()->id,
        ]);
        $collection = CollectionFactory::create([
            'owner_id' => $user->owner()->id,
        ]);
        $stream = StreamFactory::create([
            'owner_id' => $user->owner()->id,
        ]);
        $follow = $user->follow($collection);

        // Reproduce the state left by the migration which created the owners:
        // all the contents belong to the system owner.
        $database = \Minz\Database::get();
        $database->exec(<<<'SQL'
            UPDATE links SET owner_id = 'system';
            UPDATE collections SET owner_id = 'system';
            UPDATE streams SET owner_id = 'system';
            UPDATE followed_collections SET owner_id = 'system';
            DELETE FROM owners WHERE type = 'user';
        SQL);

        $response = $this->runOwnersCommand();

        $this->assertResponseCode($response, 200);
        $owner = models\UserOwner::requireBy(['user_id' => $user->id]);
        $this->assertSame($owner->id, $link->reload()->owner_id);
        $this->assertSame($owner->id, $collection->reload()->owner_id);
        $this->assertSame($owner->id, $stream->reload()->owner_id);
        $this->assertSame($owner->id, $follow->reload()->owner_id);
        $this->assertSame(models\SystemOwner::ID, $feed_link->reload()->owner_id);
    }

    public function testOwnersMergesTheDuplicatedLinks(): void
    {
        $this->installSchema();
        $user = UserFactory::create();
        $url = 'https://example.com/report';
        $kept_link = LinkFactory::create([
            'owner_id' => $user->owner()->id,
            'url' => $url,
            'created_at' => \Minz\Time::ago(2, 'days'),
            'is_hidden' => false,
        ]);
        $duplicated_link = LinkFactory::create([
            'owner_id' => $user->owner()->id,
            'url' => $url,
            'created_at' => \Minz\Time::ago(1, 'day'),
            'is_hidden' => false,
        ]);
        $collection = CollectionFactory::create([
            'owner_id' => $user->owner()->id,
        ]);
        $other_collection = CollectionFactory::create([
            'owner_id' => $user->owner()->id,
        ]);
        $oldest_published_at = \Minz\Time::ago(5, 'days');
        $kept_link->addCollection($collection, by: $user, at: \Minz\Time::ago(1, 'hour'));
        $duplicated_link->addCollection($collection, by: $user, at: $oldest_published_at);
        $duplicated_link->addCollection($other_collection, by: $user, at: \Minz\Time::ago(3, 'days'));
        $note = NoteFactory::create([
            'link_id' => $duplicated_link->id,
            'user_id' => $user->id,
            'content' => 'To read for the #thesis',
        ]);

        $response = $this->runOwnersCommand();

        $this->assertResponseCode($response, 200);
        $this->assertFalse(models\Link::exists($duplicated_link->id));
        $kept_link = $kept_link->reload();
        $this->assertSame(['thesis' => 'thesis'], $kept_link->tags);
        $this->assertSame($kept_link->id, $note->reload()->link_id);
        $link_to_collection = models\LinkToList::requireBy([
            'link_id' => $kept_link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertSame(
            $oldest_published_at->getTimestamp(),
            $link_to_collection->created_at->getTimestamp(),
        );
        $this->assertTrue(models\LinkToList::existsBy([
            'link_id' => $kept_link->id,
            'collection_id' => $other_collection->id,
        ]));
    }

    public function testOwnersHidesTheMergedLinkIfADuplicateWasHidden(): void
    {
        $this->installSchema();
        $user = UserFactory::create();
        $url = 'https://example.com/report';
        $kept_link = LinkFactory::create([
            'owner_id' => $user->owner()->id,
            'url' => $url,
            'created_at' => \Minz\Time::ago(2, 'days'),
            'is_hidden' => false,
        ]);
        LinkFactory::create([
            'owner_id' => $user->owner()->id,
            'url' => $url,
            'created_at' => \Minz\Time::ago(1, 'day'),
            'is_hidden' => true,
        ]);

        $response = $this->runOwnersCommand();

        $this->assertResponseCode($response, 200);
        $this->assertTrue($kept_link->reload()->is_hidden);
    }

    public function testOwnersFixesTheStaleUrlHashes(): void
    {
        $this->installSchema();
        $user = UserFactory::create();
        $old_url = 'HTTPS://example.com/report';
        $new_url = 'https://example.com/report';
        $link = LinkFactory::create([
            'owner_id' => $user->owner()->id,
            'url' => $old_url,
        ]);
        $feed_link = LinkFactory::create([
            'owner_id' => models\SystemOwner::get()->id,
            'url' => $new_url,
        ]);
        models\UrlStatus::markAsReadLater($user, $link->reload());
        models\UrlStatus::markAsRead($user, $feed_link->reload());

        $response = $this->runOwnersCommand();

        $this->assertResponseCode($response, 200);
        $link = $link->reload();
        $this->assertSame($new_url, $link->url);
        $this->assertSame(utils\Belt::hashUrl($new_url), $link->url_hash);
        $this->assertFalse(models\UrlStatus::existsBy([
            'user_id' => $user->id,
            'url_hash' => utils\Belt::hashUrl($old_url),
        ]));
        $url_status = models\UrlStatus::requireBy([
            'user_id' => $user->id,
            'url_hash' => utils\Belt::hashUrl($new_url),
        ]);
        $this->assertTrue($url_status->isRead());
        $this->assertTrue($url_status->isReadLater());
    }

    public function testOwnersMakesTheDeletionOfAUserDeleteItsContentsButKeepItsViews(): void
    {
        $this->installSchema();
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'owner_id' => $user->owner()->id,
        ]);
        $stream = StreamFactory::create([
            'owner_id' => $other_user->owner()->id,
        ]);
        $view = ViewFactory::create([
            'user_id' => $user->id,
            'stream_id' => $stream->id,
        ]);

        $this->runOwnersCommand();

        $user->remove();

        $this->assertFalse(models\Link::exists($link->id));
        $this->assertTrue(models\View::exists($view->id));
        $this->assertNull($view->reload()->user_id);
    }

    public function testOwnersCanBeRunAgain(): void
    {
        $this->installSchema();
        $user = UserFactory::create();
        LinkFactory::create([
            'owner_id' => $user->owner()->id,
        ]);
        $this->runOwnersCommand();

        $response = $this->runOwnersCommand();

        $this->assertResponseCode($response, 200);

        $statement = \Minz\Database::get()->prepare(<<<'SQL'
            SELECT conname FROM pg_constraint
            WHERE conrelid = CAST(:table AS regclass)
        SQL);
        $statement->execute([':table' => 'links']);
        /** @var string[] $constraint_names */
        $constraint_names = $statement->fetchAll(\PDO::FETCH_COLUMN);

        $this->assertContains('links_owner_id_fkey', $constraint_names);
        $this->assertContains('links_user_id_fkey', $constraint_names);
        $this->assertNotContains('links_user_id_tmp_fkey', $constraint_names);
        $this->assertNotContains('links_owner_id_not_null', $constraint_names);
    }

    private function installSchema(): void
    {
        \Minz\Database::reset();
        $schema = @file_get_contents(\App\Configuration::$schema_path);
        assert($schema !== false);
        $database = \Minz\Database::get();
        $database->exec($schema);
    }

    /**
     * Run the owners command until its end, and return its last response.
     */
    private function runOwnersCommand(): \Minz\Response
    {
        $responses = $this->appRun('CLI', '/migrations/owners');

        $this->assertInstanceOf(\Generator::class, $responses);

        $last_response = null;
        foreach ($responses as $response) {
            $last_response = $response;
        }

        $this->assertInstanceOf(\Minz\Response::class, $last_response);

        return $last_response;
    }
}
