<?php

namespace App\cli;

use App\models;
use App\utils;
use Minz\Database;
use Minz\Request;
use Minz\Response;
use SpiderBits\Url;

/**
 * @phpstan-import-type ResponseReturnable from Response
 * @phpstan-import-type ResponseGenerator from Response
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Migrations extends \Minz\Migration\Controller
{
    /**
     * Reset the database.
     *
     * @request_param bool force
     *     Must be set to `true` or the command will fail.
     *
     * @response 400
     *     If the environment is production, or force is not true.
     * @response 200
     *     On success.
     *
     * @see \Minz\Migration\Controller::setup
     *
     * @return ResponseReturnable
     */
    public function reset(Request $request): mixed
    {
        $environment = \App\Configuration::$environment;
        if ($environment === 'production') {
            return Response::text(400, 'You can’t reset the database in production.');
        }

        $force = $request->parameters->getBoolean('force');
        if (!$force) {
            return Response::text(400, 'You must pass the --force option to confirm.');
        }

        \Minz\Database::drop();

        $migrations_version_path = static::migrationsVersionPath();
        @unlink($migrations_version_path);

        return $this->setup($request);
    }

    /**
     * Fill the owner_id columns, then add the related indexes and constraints.
     *
     * @response 500
     *     If a step fails.
     * @response 200
     *     On success.
     *
     * @return ResponseGenerator
     */
    public function owners(): mixed
    {
        try {
            yield Response::text(200, 'Creating the owners...');
            yield Response::text(200, $this->createOwners());

            yield Response::text(200, 'Filling the owner_id columns...');
            yield Response::text(200, $this->fillOwnerIds());

            yield Response::text(200, 'Fixing the stale URL hashes...');
            yield Response::text(200, $this->fixStaleUrlHashes());

            yield Response::text(200, 'Merging the duplicated links...');
            yield Response::text(200, $this->deduplicateLinks());

            yield Response::text(200, 'Creating the indexes...');
            yield Response::text(200, $this->createIndexes());

            yield Response::text(200, 'Adding the owner_id constraints...');
            yield Response::text(200, $this->addOwnerConstraints());

            yield Response::text(200, 'Changing the user_id foreign keys...');
            yield Response::text(200, $this->changeUserForeignKeys());
        } catch (\PDOException $exception) {
            yield Response::text(500, implode("\n", [
                $exception->getMessage(),
                'Run the command again to resume.',
            ]));
        }
    }

    /**
     * Create the system owner and the owners of the users who don't have one yet.
     */
    private function createOwners(): string
    {
        models\SystemOwner::get();

        $database = Database::get();

        $statement = $database->query(<<<'SQL'
            SELECT id FROM users
            WHERE NOT EXISTS (
                SELECT 1 FROM owners WHERE owners.user_id = users.id
            )
        SQL);
        $user_ids = $statement->fetchAll(\PDO::FETCH_COLUMN);

        $statement = $database->prepare(<<<'SQL'
            INSERT INTO owners (id, created_at, type, user_id)
            VALUES (:id, :created_at, 'user', :user_id)
            ON CONFLICT DO NOTHING
        SQL);

        foreach ($user_ids as $user_id) {
            $statement->execute([
                ':id' => \Minz\Random::timebased(),
                ':created_at' => \Minz\Time::now()->format(Database\Column::DATETIME_FORMAT),
                ':user_id' => $user_id,
            ]);
        }

        $count = count($user_ids);
        return "{$count} owners created.";
    }

    /**
     * Replace the 'system' value of owner_id by the owner of user_id when existing.
     */
    private function fillOwnerIds(): string
    {
        $database = Database::get();
        $count = 0;

        foreach (['collections', 'streams', 'followed_collections'] as $table) {
            $statement = $database->prepare(<<<SQL
                UPDATE {$table}
                SET owner_id = owners.id
                FROM owners
                WHERE owners.user_id = {$table}.user_id
                AND {$table}.owner_id = 'system'
            SQL);
            $statement->execute();

            $count += $statement->rowCount();
        }

        $statement = $database->prepare(<<<'SQL'
            UPDATE links
            SET owner_id = owners.id
            FROM owners
            WHERE owners.user_id = links.user_id
            AND links.owner_id = 'system'
            AND links.id BETWEEN :first_id AND :last_id
        SQL);

        foreach ($this->linksBatches(['id']) as $links) {
            $statement->execute([
                ':first_id' => $links[0]['id'],
                ':last_id' => $links[count($links) - 1]['id'],
            ]);

            $count += $statement->rowCount();
        }

        // A large number of rows changed: refresh the stats so the planner
        // chooses good plans for the next steps.
        $database->exec('ANALYZE links, collections, streams, followed_collections');

        return "{$count} rows filled.";
    }

    /**
     * Sanitize the URLs which don't match the current sanitization rules.
     */
    private function fixStaleUrlHashes(): string
    {
        $database = Database::get();

        // Merge the statuses of the users having a status under both hashes.
        $merge_statuses_statement = $database->prepare(<<<'SQL'
            UPDATE url_statuses AS new_hash_status
            SET created_at = LEAST(new_hash_status.created_at, old_hash_status.created_at),
                read_at = LEAST(new_hash_status.read_at, old_hash_status.read_at),
                read_later_at = LEAST(new_hash_status.read_later_at, old_hash_status.read_later_at),
                dismissed_at = LEAST(new_hash_status.dismissed_at, old_hash_status.dismissed_at)
            FROM url_statuses AS old_hash_status
            WHERE old_hash_status.user_id = new_hash_status.user_id
            AND old_hash_status.url_hash = :old_url_hash
            AND new_hash_status.url_hash = :new_url_hash
        SQL);

        $delete_merged_statuses_statement = $database->prepare(<<<'SQL'
            DELETE FROM url_statuses AS old_hash_status
            USING url_statuses AS new_hash_status
            WHERE old_hash_status.user_id = new_hash_status.user_id
            AND old_hash_status.url_hash = :old_url_hash
            AND new_hash_status.url_hash = :new_url_hash
        SQL);

        $move_statuses_statement = $database->prepare(<<<'SQL'
            UPDATE url_statuses
            SET url_hash = :new_url_hash
            WHERE url_hash = :old_url_hash
        SQL);

        // The database computes the new url_hash of the links, so no need to
        // set it too.
        $change_urls_statement = $database->prepare(<<<'SQL'
            UPDATE links
            SET url = :new_url
            WHERE url_hash = :old_url_hash
        SQL);

        $count_fixed = 0;
        $count_invalid = 0;

        foreach ($this->linksBatches(['id', 'url', 'url_hash']) as $links) {
            foreach ($links as $link) {
                $sanitized_url = Url::sanitize($link['url']);

                if ($sanitized_url === $link['url']) {
                    continue;
                }

                if (!Url::isValid($sanitized_url)) {
                    $count_invalid += 1;
                    continue;
                }

                $old_url_hash = $link['url_hash'];
                $new_url_hash = utils\Belt::hashUrl($sanitized_url);

                $database->beginTransaction();

                try {
                    $merge_statuses_statement->execute([
                        ':old_url_hash' => $old_url_hash,
                        ':new_url_hash' => $new_url_hash,
                    ]);

                    $delete_merged_statuses_statement->execute([
                        ':old_url_hash' => $old_url_hash,
                        ':new_url_hash' => $new_url_hash,
                    ]);

                    $move_statuses_statement->execute([
                        ':old_url_hash' => $old_url_hash,
                        ':new_url_hash' => $new_url_hash,
                    ]);

                    $change_urls_statement->execute([
                        ':old_url_hash' => $old_url_hash,
                        ':new_url' => $sanitized_url,
                    ]);

                    $database->commit();
                } catch (\Throwable $exception) {
                    $database->rollBack();
                    throw $exception;
                }

                $count_fixed += 1;
            }
        }

        return "{$count_fixed} links fixed, {$count_invalid} ignored (invalid URL).";
    }

    /**
     * Merge the links of a same owner pointing to a same URL.
     */
    private function deduplicateLinks(): string
    {
        $database = Database::get();

        $duplicates_ids_clause = <<<'SQL'
            SELECT id FROM links
            WHERE owner_id = :owner_id
            AND url_hash = :url_hash
            AND id <> :kept_id
        SQL;

        // Move the duplicates to the collections of the kept link. In a same
        // collection, the oldest date is kept. The duplicates have the same
        // owner, so their rows have the same user_id.
        $move_collections_statement = $database->prepare(<<<SQL
            INSERT INTO links_to_collections (created_at, link_id, collection_id, user_id)
            SELECT MIN(created_at), :kept_id, collection_id, MIN(user_id)
            FROM links_to_collections
            WHERE link_id IN ({$duplicates_ids_clause})
            GROUP BY collection_id
            ON CONFLICT (link_id, collection_id) DO UPDATE
            SET created_at = LEAST(links_to_collections.created_at, EXCLUDED.created_at)
        SQL);

        $move_notes_statement = $database->prepare(<<<SQL
            UPDATE notes
            SET link_id = :kept_id
            WHERE link_id IN ({$duplicates_ids_clause})
        SQL);

        $move_mastodon_statuses_statement = $database->prepare(<<<SQL
            UPDATE mastodon_statuses
            SET link_id = :kept_id
            WHERE link_id IN ({$duplicates_ids_clause})
        SQL);

        // Don't expose a link that the user marked once as hidden.
        $hide_kept_link_statement = $database->prepare(<<<SQL
            UPDATE links
            SET is_hidden = true
            WHERE id = :kept_id
            AND EXISTS (
                SELECT 1 FROM links
                WHERE id IN ({$duplicates_ids_clause})
                AND is_hidden
            )
        SQL);

        $delete_duplicates_statement = $database->prepare(<<<SQL
            DELETE FROM links
            WHERE id IN ({$duplicates_ids_clause})
        SQL);

        // List the groups of duplicates, with the id of the oldest link of
        // each group.
        $duplicate_groups_statement = $database->query(<<<'SQL'
            SELECT owner_id, url_hash, (ARRAY_AGG(id ORDER BY created_at, id))[1] AS kept_id
            FROM links
            WHERE owner_id <> 'system'
            GROUP BY owner_id, url_hash
            HAVING COUNT(*) > 1
        SQL);

        /** @var list<array{owner_id: string, url_hash: string, kept_id: string}> $duplicate_groups */
        $duplicate_groups = $duplicate_groups_statement->fetchAll();

        foreach ($duplicate_groups as $duplicate_group) {
            $parameters = [
                ':owner_id' => $duplicate_group['owner_id'],
                ':url_hash' => $duplicate_group['url_hash'],
                ':kept_id' => $duplicate_group['kept_id'],
            ];

            $database->beginTransaction();

            try {
                $move_collections_statement->execute($parameters);
                $move_notes_statement->execute($parameters);
                $move_mastodon_statuses_statement->execute($parameters);
                $hide_kept_link_statement->execute($parameters);
                $delete_duplicates_statement->execute($parameters);

                models\Link::require($duplicate_group['kept_id'])->refreshTags();

                $database->commit();
            } catch (\Throwable $exception) {
                $database->rollBack();
                throw $exception;
            }
        }

        $count = count($duplicate_groups);
        return "{$count} groups of links merged.";
    }

    /**
     * Create the indexes on owner_id and user_id columns of the different tables.
     */
    private function createIndexes(): string
    {
        $this->createIndex(
            'idx_links_owner_id_url_hash',
            "ON links (owner_id, url_hash) WHERE owner_id <> 'system'",
            unique: true,
        );
        $this->createIndex(
            'idx_followed_collections_owner_id_collection_id',
            'ON followed_collections (owner_id, collection_id)',
            unique: true,
        );
        $this->createIndex('idx_links_user_id', 'ON links (user_id) WHERE user_id IS NOT NULL');
        $this->createIndex('idx_collections_owner_id', 'ON collections (owner_id)');
        $this->createIndex('idx_streams_owner_id', 'ON streams (owner_id)');
        $this->createIndex('idx_followed_collections_user_id', 'ON followed_collections (user_id)');
        $this->createIndex('idx_notes_user_id', 'ON notes (user_id) WHERE user_id IS NOT NULL');

        return 'Indexes created.';
    }

    /**
     * Create an index concurrently.
     *
     * An invalid index (left by a failed or interrupted creation) is dropped first.
     */
    private function createIndex(string $name, string $definition, bool $unique = false): void
    {
        $database = Database::get();

        $statement = $database->prepare(<<<'SQL'
            SELECT 1
            FROM pg_index
            JOIN pg_class ON pg_class.oid = pg_index.indexrelid
            WHERE pg_class.relname = :name
            AND NOT pg_index.indisvalid
        SQL);
        $statement->execute([':name' => $name]);
        $is_invalid = $statement->fetchColumn() !== false;

        if ($is_invalid) {
            $database->exec("DROP INDEX CONCURRENTLY {$name}");
        }

        $unique_clause = $unique ? 'UNIQUE' : '';
        $database->exec("CREATE {$unique_clause} INDEX CONCURRENTLY IF NOT EXISTS {$name} {$definition}");
    }

    /**
     * Make owner_id NOT NULL, and add its foreign key and its CHECK constraint
     * on each owned table.
     */
    private function addOwnerConstraints(): string
    {
        $this->addOwnerConstraintsTo('links', "owner_id <> 'system' OR user_id IS NULL");
        $this->addOwnerConstraintsTo('collections', "owner_id <> 'system' OR user_id IS NULL");
        $this->addOwnerConstraintsTo('streams', "owner_id <> 'system'");
        $this->addOwnerConstraintsTo('followed_collections', "owner_id <> 'system'");

        return 'Constraints added.';
    }

    private function addOwnerConstraintsTo(string $table, string $system_check): void
    {
        $not_null_check = "{$table}_owner_id_not_null";

        // SET NOT NULL relies on the validated CHECK constraint to avoid scanning the table.
        if ($this->isColumnNullable($table, 'owner_id')) {
            $this->addConstraint($table, $not_null_check, 'CHECK (owner_id IS NOT NULL)');
            Database::get()->exec("ALTER TABLE {$table} ALTER COLUMN owner_id SET NOT NULL");
        }

        if ($this->constraintExists($table, $not_null_check)) {
            Database::get()->exec("ALTER TABLE {$table} DROP CONSTRAINT {$not_null_check}");
        }

        $this->addConstraint(
            $table,
            "{$table}_owner_id_fkey",
            'FOREIGN KEY (owner_id) REFERENCES owners ON DELETE CASCADE ON UPDATE CASCADE',
        );

        $this->addConstraint($table, "{$table}_owner_id_system_check", "CHECK ({$system_check})");
    }

    /**
     * Replace the user_id foreign keys in CASCADE by foreign keys in SET NULL.
     */
    private function changeUserForeignKeys(): string
    {
        if (!$this->isColumnNullable('views', 'user_id')) {
            Database::get()->exec('ALTER TABLE views ALTER COLUMN user_id DROP NOT NULL');
        }

        $is_cascade_statement = Database::get()->prepare(<<<'SQL'
            SELECT 1
            FROM pg_constraint
            WHERE conrelid = CAST(:table AS regclass)
            AND conname = :name
            AND confdeltype = 'c'
        SQL);

        $tables = ['links', 'collections', 'streams', 'followed_collections', 'notes', 'views'];

        foreach ($tables as $table) {
            $fk_name = "{$table}_user_id_fkey";
            $temporary_fk_name = "{$table}_user_id_tmp_fkey";

            $is_cascade_statement->execute([':table' => $table, ':name' => $fk_name]);
            $is_cascade = $is_cascade_statement->fetchColumn() !== false;

            // The new foreign key is added under a temporary name, as the old
            // one still exists. It is renamed once the old one is dropped.
            if ($is_cascade) {
                $this->addConstraint(
                    $table,
                    $temporary_fk_name,
                    'FOREIGN KEY (user_id) REFERENCES users ON DELETE SET NULL ON UPDATE CASCADE',
                );

                Database::get()->exec("ALTER TABLE {$table} DROP CONSTRAINT {$fk_name}");
            }

            if ($this->constraintExists($table, $temporary_fk_name)) {
                Database::get()->exec(
                    "ALTER TABLE {$table} RENAME CONSTRAINT {$temporary_fk_name} TO {$fk_name}"
                );
            }
        }

        return 'Foreign keys changed.';
    }

    /**
     * Add a constraint as NOT VALID (brief lock), then validate it (does not
     * block the writes).
     */
    private function addConstraint(string $table, string $name, string $definition): void
    {
        if (!$this->constraintExists($table, $name)) {
            Database::get()->exec("ALTER TABLE {$table} ADD CONSTRAINT {$name} {$definition} NOT VALID");
        }

        Database::get()->exec("ALTER TABLE {$table} VALIDATE CONSTRAINT {$name}");
    }

    /**
     * Yield the links by batches of 1000, ordered by id.
     *
     * @param literal-string[] $columns
     *
     * @return \Generator<int, non-empty-list<array<string, string>>>
     */
    private function linksBatches(array $columns): \Generator
    {
        $columns = implode(',', $columns);

        $statement = Database::get()->prepare(<<<SQL
            SELECT {$columns} FROM links
            WHERE id > :last_id
            ORDER BY id
            LIMIT 1000
        SQL);

        $last_id = '';

        while (true) {
            $statement->execute([':last_id' => $last_id]);

            /** @var list<array<string, string>> $links */
            $links = $statement->fetchAll();

            if (!$links) {
                return;
            }

            yield $links;

            $last_id = $links[count($links) - 1]['id'];
        }
    }

    private function constraintExists(string $table, string $name): bool
    {
        $statement = Database::get()->prepare(<<<'SQL'
            SELECT 1
            FROM pg_constraint
            WHERE conrelid = CAST(:table AS regclass)
            AND conname = :name
        SQL);
        $statement->execute([':table' => $table, ':name' => $name]);

        return $statement->fetchColumn() !== false;
    }

    private function isColumnNullable(string $table, string $column): bool
    {
        $statement = Database::get()->prepare(<<<'SQL'
            SELECT is_nullable
            FROM information_schema.columns
            WHERE table_schema = current_schema()
            AND table_name = :table
            AND column_name = :column
        SQL);
        $statement->execute([':table' => $table, ':column' => $column]);

        return $statement->fetchColumn() === 'YES';
    }
}
