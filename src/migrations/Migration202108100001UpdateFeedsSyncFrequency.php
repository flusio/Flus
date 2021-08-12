<?php

namespace flusio\migrations;

class Migration202108100001UpdateFeedsSyncFrequency
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE jobs
            SET frequency = '+15 seconds'
            WHERE name = 'flusio\jobs\scheduled\FeedsSync';
        SQL);

        return true;
    }

    public function rollback()
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