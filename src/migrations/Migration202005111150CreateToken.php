<?php

namespace App\migrations;

class Migration202005111150CreateToken
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE TABLE tokens (
                token TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                expired_at TIMESTAMPTZ NOT NULL,
                invalidated_at TIMESTAMPTZ
            );
        SQL;

        $database->exec($sql);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            DROP TABLE tokens;
        SQL;

        $database->exec($sql);

        return true;
    }
}
