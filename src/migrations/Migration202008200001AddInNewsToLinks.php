<?php

namespace flusio\migrations;

class Migration202008200001AddInNewsToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN in_news BOOLEAN NOT NULL DEFAULT false;
        SQL);

        return true;
    }
}
