<?php

namespace flusio\migrations;

class Migration202202080001RenameViaColumns
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            BEGIN;

            UPDATE links
            SET via_type = 'collection'
            WHERE via_type = 'followed';

            ALTER TABLE links
            ADD COLUMN via_resource_id TEXT;

            UPDATE links
            SET via_resource_id = via_collection_id
            WHERE via_collection_id IS NOT NULL;

            ALTER TABLE links
            DROP COLUMN via_link_id,
            DROP COLUMN via_collection_id;

            COMMIT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            BEGIN;

            UPDATE links
            SET via_type = 'followed'
            WHERE via_type = 'collection';

            ALTER TABLE links
            ADD COLUMN via_link_id TEXT REFERENCES links ON DELETE SET NULL ON UPDATE CASCADE,
            ADD COLUMN via_collection_id TEXT REFERENCES collections ON DELETE SET NULL ON UPDATE CASCADE;

            CREATE INDEX idx_links_via_link_id ON links(via_link_id) WHERE via_link_id IS NOT NULL;
            CREATE INDEX idx_links_via_collection_id ON links(via_collection_id) WHERE via_collection_id IS NOT NULL;

            UPDATE links
            SET via_collection_id = via_resource_id
            WHERE via_resource_id IS NOT NULL;

            ALTER TABLE links
            DROP COLUMN via_resource_id;

            COMMIT;
        SQL);

        return true;
    }
}
