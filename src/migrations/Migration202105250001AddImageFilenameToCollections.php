<?php

namespace App\migrations;

class Migration202105250001AddImageFilenameToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ADD COLUMN image_filename TEXT,
            ADD COLUMN image_fetched_at TIMESTAMPTZ;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            DROP COLUMN image_filename,
            DROP COLUMN image_fetched_at;
        SQL);

        return true;
    }
}
