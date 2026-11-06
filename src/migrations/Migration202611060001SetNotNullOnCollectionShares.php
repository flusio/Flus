<?php

namespace App\migrations;

class Migration202611060001SetNotNullOnCollectionShares
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collection_shares
            ALTER COLUMN user_id SET NOT NULL,
            ALTER COLUMN collection_id SET NOT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collection_shares
            ALTER COLUMN user_id DROP NOT NULL,
            ALTER COLUMN collection_id DROP NOT NULL;
        SQL);

        return true;
    }
}
