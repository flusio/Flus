<?php

namespace App\migrations;

class Migration202108250001DeleteLinksFetcherJob
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DELETE FROM jobs
            WHERE name = 'flusio\jobs\scheduled\LinksFetcher';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        // Do nothing on purpose
        return true;
    }
}
