<?php

namespace App\migrations;

class Migration202610120001AddIndexLinksFeedEntryId
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_links_feed_entry_id
            ON links USING hash(feed_entry_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_feed_entry_id;
        SQL);

        return true;
    }
}
