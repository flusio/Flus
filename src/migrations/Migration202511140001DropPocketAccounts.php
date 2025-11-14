<?php

namespace App\migrations;

class Migration202511140001DropPocketAccounts
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE pocket_accounts;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE pocket_accounts (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                username TEXT,
                request_token TEXT,
                access_token TEXT,
                error INTEGER,

                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );
        SQL);

        return true;
    }
}
