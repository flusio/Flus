<?php

namespace App\migrations;

class Migration202611060002AddIndexCollectionSharesCollectionId
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_collection_shares_collection_id
            ON collection_shares(collection_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_collection_shares_collection_id;
        SQL);

        return true;
    }
}
