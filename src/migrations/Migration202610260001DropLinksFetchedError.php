<?php

namespace App\migrations;

class Migration202610260001DropLinksFetchedError
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links DROP COLUMN fetched_error;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links ADD COLUMN fetched_error TEXT;
        SQL);

        return true;
    }
}
