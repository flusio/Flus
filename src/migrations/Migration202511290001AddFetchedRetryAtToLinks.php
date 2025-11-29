<?php

namespace App\migrations;

class Migration202511290001AddFetchedRetryAtToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN fetched_retry_at TIMESTAMPTZ DEFAULT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN fetched_retry_at;
        SQL);

        return true;
    }
}
