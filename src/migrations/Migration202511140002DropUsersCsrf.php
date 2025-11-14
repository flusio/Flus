<?php

namespace App\migrations;

class Migration202511140002DropUsersCsrf
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users DROP COLUMN csrf;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN csrf TEXT NOT NULL DEFAULT '';
        SQL);

        return true;
    }
}
