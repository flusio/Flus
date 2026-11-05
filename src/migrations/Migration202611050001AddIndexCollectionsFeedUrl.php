<?php

namespace App\migrations;

class Migration202611050001AddIndexCollectionsFeedUrl
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_collections_feed_url
            ON collections USING hash (feed_url);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_collections_feed_url;
        SQL);

        return true;
    }
}
