<?php

namespace App\migrations;

class Migration202403100001AddAcceptContactToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN accept_contact BOOLEAN NOT NULL DEFAULT false;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN accept_contact;
        SQL);

        return true;
    }
}
