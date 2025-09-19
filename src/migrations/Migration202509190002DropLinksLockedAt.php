<?php

namespace App\migrations;

class Migration202509190002DropLinksLockedAt
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN locked_at
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN locked_at TIMESTAMPTZ
        SQL);

        return true;
    }
}
