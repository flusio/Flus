<?php

namespace App\migrations;

class Migration202511300001AddMissingIndexesOnLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_links_fetched_at ON links(fetched_at) WHERE fetched_at IS NULL;
            CREATE INDEX idx_links_fetched_retry_at ON links(fetched_retry_at) WHERE fetched_retry_at IS NOT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_fetched_at;
            DROP INDEX idx_links_fetched_retry_at;
        SQL);

        return true;
    }
}
