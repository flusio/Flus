<?php

namespace App\migrations;

class Migration202605270001AddSourceIdToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN source_id TEXT REFERENCES collections ON DELETE SET NULL ON UPDATE CASCADE;

            CREATE INDEX idx_links_source_id ON links(source_id) WHERE source_id IS NOT NULL;

            DROP INDEX idx_links_origin;

            UPDATE links
            SET source_id = source_resource_id
            WHERE source_type = 'collection'
            AND EXISTS (
                SELECT 1
                FROM collections
                WHERE id = source_resource_id
            );
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_links_origin ON links(origin) WHERE origin != '';

            DROP INDEX idx_links_source_id;

            ALTER TABLE links
            DROP COLUMN source_id;
        SQL);

        return true;
    }
}
