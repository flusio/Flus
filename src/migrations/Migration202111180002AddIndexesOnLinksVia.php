<?php

namespace flusio\migrations;

class Migration202111180002AddIndexesOnLinksVia
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_links_via_link_id ON links(via_link_id) WHERE via_link_id IS NOT NULL;
            CREATE INDEX idx_links_via_collection_id ON links(via_collection_id) WHERE via_collection_id IS NOT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_via_link_id;
            DROP INDEX idx_links_via_collection_id;
        SQL);

        return true;
    }
}
