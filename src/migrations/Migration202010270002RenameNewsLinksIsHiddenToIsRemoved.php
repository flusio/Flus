<?php

namespace flusio\migrations;

class Migration202010270002RenameNewsLinksIsHiddenToIsRemoved
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            RENAME COLUMN is_hidden TO is_removed;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            RENAME COLUMN is_removed TO is_hidden;
        SQL);

        return true;
    }
}
