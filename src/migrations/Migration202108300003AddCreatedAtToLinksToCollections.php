<?php

namespace App\migrations;

class Migration202108300003AddCreatedAtToLinksToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->beginTransaction();

        $database->exec(<<<'SQL'
            ALTER TABLE links_to_collections
            ADD COLUMN created_at TIMESTAMPTZ;

            UPDATE links_to_collections lc
            SET created_at = l.created_at
            FROM links l
            WHERE lc.link_id = l.id;

            ALTER TABLE links_to_collections
            ALTER COLUMN created_at SET NOT NULL;
        SQL);

        $database->commit();

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links_to_collections
            DROP COLUMN created_at;
        SQL);

        return true;
    }
}
