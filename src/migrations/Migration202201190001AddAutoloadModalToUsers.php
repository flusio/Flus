<?php

namespace App\migrations;

class Migration202201190001AddAutoloadModalToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN autoload_modal TEXT NOT NULL DEFAULT '';

            UPDATE users SET autoload_modal = 'showcase navigation';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN autoload_modal
        SQL);

        return true;
    }
}
