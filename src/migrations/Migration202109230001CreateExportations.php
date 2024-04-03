<?php

namespace App\migrations;

class Migration202109230001CreateExportations
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE exportations (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                status TEXT NOT NULL,
                error TEXT NOT NULL DEFAULT '',
                filepath TEXT NOT NULL DEFAULT '',
                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE exportations;
        SQL);

        return true;
    }
}
