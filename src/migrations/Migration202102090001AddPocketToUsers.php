<?php

namespace flusio\migrations;

class Migration202102090001AddPocketToUsers
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN pocket_request_token TEXT,
            ADD COLUMN pocket_access_token TEXT,
            ADD COLUMN pocket_username TEXT,
            ADD COLUMN pocket_error INTEGER;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN pocket_request_token,
            DROP COLUMN pocket_access_token,
            DROP COLUMN pocket_username,
            DROP COLUMN pocket_error;
        SQL);

        return true;
    }
}
