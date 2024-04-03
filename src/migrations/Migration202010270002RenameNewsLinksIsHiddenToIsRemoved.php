<?php

namespace App\migrations;

class Migration202010270002RenameNewsLinksIsHiddenToIsRemoved
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            RENAME COLUMN is_hidden TO is_removed;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            RENAME COLUMN is_removed TO is_hidden;
        SQL);

        return true;
    }
}
