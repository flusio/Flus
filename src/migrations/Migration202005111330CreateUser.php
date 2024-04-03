<?php

namespace App\migrations;

class Migration202005111330CreateUser
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE TABLE users (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                email TEXT UNIQUE NOT NULL,
                username TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                validated_at TIMESTAMPTZ,
                validation_token TEXT REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE
            );
        SQL;

        $database->exec($sql);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            DROP TABLE users;
        SQL;

        $database->exec($sql);

        return true;
    }
}
