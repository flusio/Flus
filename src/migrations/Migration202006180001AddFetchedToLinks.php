<?php

namespace flusio\migrations;

class Migration202006180001AddFetchedToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE links
            ADD COLUMN fetched_at TIMESTAMPTZ,
            ADD COLUMN fetched_code INTEGER NOT NULL DEFAULT 0,
            ADD COLUMN fetched_error TEXT;
        SQL;

        $database->exec($sql);

        return true;
    }
}
