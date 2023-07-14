<?php

namespace flusio\migrations;

class Migration202210040001AddOptionCompactModeToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN option_compact_mode BOOLEAN NOT NULL DEFAULT false
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN option_compact_mode
        SQL);

        return true;
    }
}
