<?php

namespace App\migrations;

class Migration202610100001CreateFetchErrors
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE fetch_errors (
                id BIGSERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                link_id TEXT NOT NULL REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
                content TEXT NOT NULL
            );

            CREATE UNIQUE INDEX idx_fetch_errors_link_id ON fetch_errors(link_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_fetch_errors_link_id;

            DROP TABLE fetch_errors;
        SQL);

        return true;
    }
}
