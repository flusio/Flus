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

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN in_news;
        SQL);

        return true;
    }
}
