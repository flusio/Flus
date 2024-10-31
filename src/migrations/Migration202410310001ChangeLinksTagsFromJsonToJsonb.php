<?php

namespace App\migrations;

class Migration202410310001ChangeLinksTagsFromJsonToJsonb
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ALTER COLUMN tags
            SET DATA TYPE JSONB
            USING tags::jsonb;

            CREATE INDEX idx_links_tags ON links USING GIN (tags);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_tags;

            ALTER TABLE links
            ALTER COLUMN tags
            SET DATA TYPE JSON
            USING tags::json;
        SQL);

        return true;
    }
}
