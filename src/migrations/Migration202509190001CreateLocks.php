<?php

namespace App\migrations;

class Migration202509190001CreateLocks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE locks (
                key TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                expired_at TIMESTAMPTZ NOT NULL
            );
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE locks;
        SQL);

        return true;
    }
}
