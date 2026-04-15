<?php

namespace App\migrations;

class Migration202604150001AddBiographyToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN biography TEXT NOT NULL DEFAULT '';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN biography;
        SQL);

        return true;
    }
}
