<?php

namespace App\migrations;

class Migration202207210001CreateCollectionShares
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE collection_shares (
                id BIGSERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                type TEXT NOT NULL,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
                collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_collection_shares ON collection_shares(user_id, collection_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_collection_shares;
            DROP TABLE collection_shares;
        SQL);

        return true;
    }
}
