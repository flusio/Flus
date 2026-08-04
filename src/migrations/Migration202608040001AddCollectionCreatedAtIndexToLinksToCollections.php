<?php

namespace App\migrations;

class Migration202608040001AddCollectionCreatedAtIndexToLinksToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        // The queries listing or counting the links of a stream all filter on
        // "collection_id = … AND created_at BETWEEN … AND …", which the previous
        // index cannot serve efficiently.
        $database->exec(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_links_to_collections_collection_id_created_at
            ON links_to_collections(collection_id, created_at);

            DROP INDEX IF EXISTS idx_links_to_collections_collection_id;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_links_to_collections_collection_id
            ON links_to_collections(collection_id);

            DROP INDEX IF EXISTS idx_links_to_collections_collection_id_created_at;
        SQL);

        return true;
    }
}
