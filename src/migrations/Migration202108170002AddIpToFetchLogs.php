<?php

namespace flusio\migrations;

class Migration202108170002AddIpToFetchLogs
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE fetch_logs
            ADD COLUMN ip TEXT;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE fetch_logs
            DROP COLUMN ip;
        SQL);

        return true;
    }
}
