<?php

namespace App\migrations;

class Migration202010270003AddIsReadToNewsLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            ADD COLUMN is_read BOOLEAN NOT NULL DEFAULT false;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            DROP COLUMN is_read;
        SQL);

        return true;
    }
}
