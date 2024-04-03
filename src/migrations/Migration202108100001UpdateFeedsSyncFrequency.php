<?php

namespace App\migrations;

class Migration202108100001UpdateFeedsSyncFrequency
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE jobs
            SET frequency = '+15 seconds'
            WHERE name = 'flusio\jobs\scheduled\FeedsSync';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE jobs
            SET frequency = '+10 minutes'
            WHERE name = 'flusio\jobs\scheduled\FeedsSync';
        SQL);

        return true;
    }
}
