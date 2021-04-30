<?php

namespace flusio\migrations;

class Migration202104300001AddFetchedCountToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN fetched_count INTEGER NOT NULL DEFAULT 0;

            UPDATE links SET fetched_count = 1
            WHERE fetched_at IS NOT NULL;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN fetched_count;
        SQL);

        return true;
    }
}
