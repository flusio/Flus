<?php

namespace flusio\migrations;

class Migration202008050001AddIsPublicToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN is_public BOOLEAN NOT NULL DEFAULT false;
        SQL);

        return true;
    }
}
