<?php

namespace App\migrations;

class Migration202611250001AddUserIdToLinksToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links_to_collections
            ADD COLUMN user_id TEXT REFERENCES users ON DELETE SET NULL ON UPDATE CASCADE;

            UPDATE links_to_collections lc
            SET user_id = l.user_id
            FROM links l
            WHERE l.id = lc.link_id
            AND l.user_id IS NOT NULL;

            CREATE INDEX idx_links_to_collections_user_id
            ON links_to_collections(user_id)
            WHERE user_id IS NOT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links_to_collections DROP COLUMN user_id;
        SQL);

        return true;
    }
}
