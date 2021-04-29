<?php

namespace flusio\migrations;

class Migration202104290001CreateFetchLogs
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE fetch_logs (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                url TEXT NOT NULL,
                host TEXT NOT NULL
            );
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE fetch_logs;
        SQL);

        return true;
    }
}
