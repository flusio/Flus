<?php

namespace flusio\migrations;

class Migration202008240001AddIsPublicToCollections
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ADD COLUMN is_public BOOLEAN NOT NULL DEFAULT false;
        SQL);

        return true;
    }
}
