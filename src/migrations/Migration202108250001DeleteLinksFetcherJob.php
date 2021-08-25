<?php

namespace flusio\migrations;

class Migration202108250001DeleteLinksFetcherJob
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DELETE FROM jobs
            WHERE name = 'flusio\jobs\scheduled\LinksFetcher';
        SQL);

        return true;
    }

    public function rollback()
    {
        // Do nothing on purpose
        return true;
    }
}
