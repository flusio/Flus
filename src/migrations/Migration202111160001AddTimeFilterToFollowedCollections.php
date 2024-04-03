<?php

namespace App\migrations;

class Migration202111160001AddTimeFilterToFollowedCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections
            ADD COLUMN time_filter TEXT NOT NULL DEFAULT 'normal';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections
            DROP COLUMN time_filter;
        SQL);

        return true;
    }
}
