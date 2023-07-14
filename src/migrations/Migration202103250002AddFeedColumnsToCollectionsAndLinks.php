<?php

namespace flusio\migrations;

class Migration202103250002AddFeedColumnsToCollectionsAndLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ADD COLUMN feed_url TEXT,
            ADD COLUMN feed_site_url TEXT,
            ADD COLUMN feed_fetched_code INTEGER NOT NULL DEFAULT 0,
            ADD COLUMN feed_fetched_at TIMESTAMPTZ,
            ADD COLUMN feed_fetched_error TEXT;

            ALTER TABLE links
            ADD COLUMN feed_entry_id TEXT,
            ADD COLUMN feed_published_at TIMESTAMPTZ;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            DROP COLUMN feed_url,
            DROP COLUMN feed_site_url,
            DROP COLUMN feed_fetched_code,
            DROP COLUMN feed_fetched_at,
            DROP COLUMN feed_fetched_error;

            ALTER TABLE links
            DROP COLUMN feed_entry_id,
            DROP COLUMN feed_published_at;
        SQL);

        return true;
    }
}
