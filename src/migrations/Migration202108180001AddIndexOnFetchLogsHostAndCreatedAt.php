<?php

namespace App\migrations;

class Migration202108180001AddIndexOnFetchLogsHostAndCreatedAt
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_fetch_logs_host_created_at ON fetch_logs(host, created_at);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_fetch_logs_host_created_at;
        SQL);

        return true;
    }
}
