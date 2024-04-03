<?php

namespace App\migrations;

class Migration202008240001AddIsPublicToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ADD COLUMN is_public BOOLEAN NOT NULL DEFAULT false;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            DROP COLUMN is_public;
        SQL);

        return true;
    }
}
