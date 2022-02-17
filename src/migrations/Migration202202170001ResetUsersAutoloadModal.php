<?php

namespace flusio\migrations;

class Migration202202170001ResetUsersAutoloadModal
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE users SET autoload_modal = 'showcase navigation'
            WHERE created_at >= '2022-01-19';
        SQL);

        return true;
    }

    public function rollback()
    {
        // Do nothing on purpose
        return true;
    }
}
