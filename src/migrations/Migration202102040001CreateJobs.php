<?php

namespace flusio\migrations;

class Migration202102040001CreateJobs
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE jobs (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                handler JSON NOT NULL,
                perform_at TIMESTAMPTZ NOT NULL,
                locked_at TIMESTAMPTZ,
                number_attempts INTEGER NOT NULL DEFAULT 0,
                last_error TEXT,
                failed_at TIMESTAMPTZ
            );
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE jobs;
        SQL);

        return true;
    }
}
