<?php

namespace App\migrations;

class Migration202108240001AddFeedLastHashToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ADD COLUMN feed_last_hash TEXT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            DROP COLUMN feed_last_hash;
        SQL);

        return true;
    }
}
