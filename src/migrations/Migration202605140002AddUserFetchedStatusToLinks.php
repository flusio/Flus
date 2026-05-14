<?php

namespace App\migrations;

class Migration202605140002AddUserFetchedStatusToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN user_fetched_status TEXT NOT NULL DEFAULT 'unset';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN user_fetched_status;
        SQL);

        return true;
    }
}
