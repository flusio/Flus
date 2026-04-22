<?php

namespace App\migrations;

class Migration202604220001AddOptionColorSchemeToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN option_color_scheme TEXT NOT NULL DEFAULT 'system';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN option_color_scheme;
        SQL);

        return true;
    }
}
