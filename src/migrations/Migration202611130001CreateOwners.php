<?php

namespace App\migrations;

class Migration202611130001CreateOwners
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        // The owner_id columns are added with a default value that is dropped
        // right after. This is instantaneous. The real values will be set later
        // with a dedicated command/migration.
        $database->exec(<<<'SQL'
            CREATE TABLE owners (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                type TEXT NOT NULL,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,

                CHECK (
                    (type = 'user' AND user_id IS NOT NULL)
                    OR (type = 'system' AND user_id IS NULL)
                )
            );

            CREATE UNIQUE INDEX idx_owners_user_id ON owners(user_id);

            ALTER TABLE links ADD COLUMN owner_id TEXT DEFAULT 'system';
            ALTER TABLE links ALTER COLUMN owner_id DROP DEFAULT;

            ALTER TABLE collections ADD COLUMN owner_id TEXT DEFAULT 'system';
            ALTER TABLE collections ALTER COLUMN owner_id DROP DEFAULT;

            ALTER TABLE streams ADD COLUMN owner_id TEXT DEFAULT 'system';
            ALTER TABLE streams ALTER COLUMN owner_id DROP DEFAULT;

            ALTER TABLE followed_collections ADD COLUMN owner_id TEXT DEFAULT 'system';
            ALTER TABLE followed_collections ALTER COLUMN owner_id DROP DEFAULT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections DROP COLUMN owner_id;
            ALTER TABLE streams DROP COLUMN owner_id;
            ALTER TABLE collections DROP COLUMN owner_id;
            ALTER TABLE links DROP COLUMN owner_id;

            DROP TABLE owners;
        SQL);

        return true;
    }
}
