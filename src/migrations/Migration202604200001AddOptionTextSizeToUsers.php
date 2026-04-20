<?php

namespace App\migrations;

class Migration202604200001AddOptionTextSizeToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN option_text_size TEXT NOT NULL DEFAULT 'medium';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN option_text_size;
        SQL);

        return true;
    }
}
