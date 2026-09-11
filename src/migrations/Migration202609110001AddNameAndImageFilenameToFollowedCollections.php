<?php

namespace App\migrations;

class Migration202609110001AddNameAndImageFilenameToFollowedCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections
            ADD COLUMN name TEXT NOT NULL DEFAULT '',
            ADD COLUMN image_filename TEXT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE followed_collections
            DROP COLUMN name,
            DROP COLUMN image_filename;
        SQL);

        return true;
    }
}
