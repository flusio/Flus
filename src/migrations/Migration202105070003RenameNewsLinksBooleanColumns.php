<?php

namespace flusio\migrations;

class Migration202105070003RenameNewsLinksBooleanColumns
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            ADD COLUMN read_at TIMESTAMPTZ,
            ADD COLUMN removed_at TIMESTAMPTZ;

            UPDATE news_links
            SET read_at = created_at
            WHERE is_read = true;

            UPDATE news_links
            SET removed_at = created_at
            WHERE is_removed = true;

            ALTER TABLE news_links
            DROP COLUMN is_read,
            DROP COLUMN is_removed;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            ADD COLUMN is_read BOOLEAN NOT NULL DEFAULT false,
            ADD COLUMN is_removed BOOLEAN NOT NULL DEFAULT false;

            UPDATE news_links
            SET is_read = true
            WHERE read_at IS NOT NULL;

            UPDATE news_links
            SET is_removed = true
            WHERE removed_at IS NOT NULL;

            ALTER TABLE news_links
            DROP COLUMN read_at,
            DROP COLUMN removed_at;
        SQL);

        return true;
    }
}
