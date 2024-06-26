<?php

namespace App\migrations;

class Migration202108300004AddPublishedAtToNewsLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->beginTransaction();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            ADD COLUMN published_at TIMESTAMPTZ;

            UPDATE news_links nl
            SET published_at = l.created_at
            FROM links l
            WHERE nl.link_id = l.id;
        SQL);

        $database->commit();

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            DROP COLUMN published_at;
        SQL);

        return true;
    }
}
