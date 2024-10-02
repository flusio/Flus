<?php

namespace App\migrations;

class Migration202410020002AddCreatedAtIndexOnLinksToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_links_to_collections_created_at ON links_to_collections(created_at);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_to_collections_created_at;
        SQL);

        return true;
    }
}
