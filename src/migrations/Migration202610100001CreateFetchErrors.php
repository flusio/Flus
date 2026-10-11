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

                url TEXT NOT NULL,
                type TEXT NOT NULL,
                code INTEGER NOT NULL,
                content TEXT NOT NULL
            );

            CREATE INDEX idx_fetch_errors_url ON fetch_errors USING hash(url);
            CREATE INDEX idx_fetch_errors_created_at ON fetch_errors(created_at);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_fetch_errors_created_at;
            DROP INDEX idx_fetch_errors_url;

            DROP TABLE fetch_errors;
        SQL);

        return true;
    }
}
