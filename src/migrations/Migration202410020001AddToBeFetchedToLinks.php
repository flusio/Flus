<?php

namespace App\migrations;

class Migration202410020001AddToBeFetchedToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN to_be_fetched BOOLEAN NOT NULL DEFAULT true;

            UPDATE links
            SET to_be_fetched = false
            WHERE fetched_at IS NOT NULL
            AND (
                (fetched_code >= 200 AND fetched_code < 300)
                OR fetched_count > 25
            );

            CREATE INDEX idx_links_to_be_fetched ON links(to_be_fetched) WHERE to_be_fetched = true;

            DROP INDEX idx_links_fetched_at;
            DROP INDEX idx_links_fetched_code;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_links_fetched_at ON links(fetched_at) WHERE fetched_at IS NULL;
            CREATE INDEX idx_links_fetched_code ON links(fetched_code) WHERE fetched_code < 200 OR fetched_code >= 300;

            DROP INDEX idx_links_to_be_fetched;

            ALTER TABLE links
            DROP COLUMN to_be_fetched;
        SQL);

        return true;
    }
}
