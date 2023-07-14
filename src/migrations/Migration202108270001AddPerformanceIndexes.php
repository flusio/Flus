<?php

namespace flusio\migrations;

class Migration202108270001AddPerformanceIndexes
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_links_fetched_at ON links(fetched_at);
            CREATE INDEX idx_collections_feed_fetched_at ON collections(feed_fetched_at);
            CREATE INDEX idx_fetch_logs_created_at ON fetch_logs(created_at);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_fetched_at;
            DROP INDEX idx_collections_feed_fetched_at;
            DROP INDEX idx_fetch_logs_created_at;
        SQL);

        return true;
    }
}
