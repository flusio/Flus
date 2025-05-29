<?php

namespace App\migrations;

class Migration202505290001AddScopeToSessions
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE sessions
            ADD COLUMN scope TEXT NOT NULL DEFAULT 'browser';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE sessions
            DROP COLUMN scope;
        SQL);

        return true;
    }
}
