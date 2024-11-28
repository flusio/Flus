<?php

namespace App\migrations;

class Migration202411280001AddLastActivityAtToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN last_activity_at TIMESTAMPTZ NOT NULL DEFAULT date_trunc('second', NOW()),
            ADD COLUMN deletion_notified_at TIMESTAMPTZ;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN last_activity_at,
            DROP COLUMN deletion_notified_at;
        SQL);

        return true;
    }
}
