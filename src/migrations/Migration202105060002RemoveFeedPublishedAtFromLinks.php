<?php

namespace App\migrations;

class Migration202105060002RemoveFeedPublishedAtFromLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE links
            SET created_at = feed_published_at
            WHERE feed_published_at IS NOT NULL;

            ALTER TABLE links
            DROP COLUMN feed_published_at;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN feed_published_at TIMESTAMPTZ;

            UPDATE links
            SET feed_published_at = created_at
            WHERE feed_entry_id IS NOT NULL;
        SQL);

        return true;
    }
}
