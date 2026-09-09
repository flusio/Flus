<?php

namespace App\migrations;

class Migration202609090002DeleteStatusesCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        // The "read later", "read" and "dismissed" states now live in the
        // url_statuses table: these collections are no longer used.
        $database->exec(<<<'SQL'
            DELETE FROM collections
            WHERE type IN ('bookmarks', 'read', 'never');
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        // do nothing on purpose
        return true;
    }
}
