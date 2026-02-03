<?php

namespace App\migrations;

class Migration202602030001DropToBeFetchedFromLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_to_be_fetched;

            ALTER TABLE links DROP COLUMN to_be_fetched;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN to_be_fetched BOOLEAN NOT NULL DEFAULT true;

            CREATE INDEX idx_links_to_be_fetched ON links(to_be_fetched) WHERE to_be_fetched = true;
        SQL);

        return true;
    }
}
