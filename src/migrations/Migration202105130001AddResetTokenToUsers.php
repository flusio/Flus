<?php

namespace flusio\migrations;

class Migration202105130001AddResetTokenToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN reset_token TEXT REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN reset_token;
        SQL);

        return true;
    }
}
