<?php

namespace App\migrations;

class Migration202609080003DropGroupIdFromFollowedCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        // Delete the groups that are not attached to any owned collection:
        // they would be of no use once the column is dropped.
        $database->exec(<<<'SQL'
            DELETE FROM groups g
            WHERE NOT EXISTS (
                SELECT 1 FROM collections c WHERE c.group_id = g.id
            );
        SQL);

        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections DROP COLUMN group_id;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        // Note that the deleted groups are not restored.
        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections
            ADD COLUMN group_id TEXT REFERENCES groups ON DELETE SET NULL ON UPDATE CASCADE;
        SQL);

        return true;
    }
}
