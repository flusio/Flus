<?php

namespace flusio\migrations;

class Migration202010210001AddConfirmedPasswordAtToSessions
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE sessions
            ADD COLUMN confirmed_password_at TIMESTAMPTZ;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE sessions
            DROP COLUMN confirmed_password_at;
        SQL);

        return true;
    }
}
