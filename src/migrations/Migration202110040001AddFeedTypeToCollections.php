<?php

namespace App\migrations;

class Migration202110040001AddFeedTypeToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ADD COLUMN feed_type TEXT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            DROP COLUMN feed_type;
        SQL);

        return true;
    }
}
