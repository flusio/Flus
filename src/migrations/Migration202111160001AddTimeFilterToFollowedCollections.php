<?php

namespace flusio\migrations;

class Migration202111160001AddTimeFilterToFollowedCollections
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections
            ADD COLUMN time_filter TEXT NOT NULL DEFAULT 'normal';
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections
            DROP COLUMN time_filter;
        SQL);

        return true;
    }
}
