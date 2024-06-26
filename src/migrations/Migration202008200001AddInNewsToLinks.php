<?php

namespace App\migrations;

class Migration202008200001AddInNewsToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN in_news BOOLEAN NOT NULL DEFAULT false;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN in_news;
        SQL);

        return true;
    }
}
