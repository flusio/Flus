<?php

namespace flusio\migrations;

class Migration202307030001MigrateJobs
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            TRUNCATE jobs;

            ALTER TABLE jobs
            ADD COLUMN updated_at TIMESTAMPTZ NOT NULL,
            ADD COLUMN args JSON NOT NULL DEFAULT '{}',
            DROP COLUMN handler,
            ALTER COLUMN last_error SET DEFAULT '',
            ALTER COLUMN last_error SET NOT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            TRUNCATE jobs;

            ALTER TABLE jobs
            DROP COLUMN updated_at,
            DROP COLUMN args,
            ADD COLUMN handler JSON NOT NULL,
            ALTER COLUMN last_error DROP DEFAULT,
            ALTER COLUMN last_error DROP NOT NULL;
        SQL);

        return true;
    }
}
