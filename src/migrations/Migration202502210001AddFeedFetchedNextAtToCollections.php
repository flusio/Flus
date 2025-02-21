<?php

namespace App\migrations;

class Migration202502210001AddFeedFetchedNextAtToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ADD COLUMN feed_fetched_next_at TIMESTAMPTZ;

            CREATE INDEX idx_collections_feed_fetched_next_at ON collections(feed_fetched_next_at);
            DROP INDEX idx_collections_feed_fetched_at;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_collections_feed_fetched_at ON collections(feed_fetched_at);
            DROP INDEX idx_collections_feed_fetched_next_at;

            ALTER TABLE collections
            DROP COLUMN feed_fetched_next_at;
        SQL);

        return true;
    }
}
