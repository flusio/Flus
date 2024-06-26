<?php

namespace App\migrations;

class Migration202105060001ForceLinksFeedEntryId
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE links
            SET feed_entry_id = url
            WHERE (feed_entry_id = '' OR feed_entry_id IS NULL)
            AND feed_published_at IS NOT NULL
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        // Do nothing on purpose
        return true;
    }
}
