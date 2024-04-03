<?php

namespace App\migrations;

class Migration202105030001DeleteOutdatedJobs
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DELETE FROM jobs
            WHERE name = 'flusio\jobs\scheduled\CacheCleaner'
            OR name = 'flusio\jobs\scheduled\ResetDemo';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        // Do nothing on purpose
        return true;
    }
}
