<?php

namespace App\migrations;

class Migration202005181700AddLocaleToUser
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE users
            ADD COLUMN locale TEXT NOT NULL DEFAULT 'en_GB';
        SQL;

        $database->exec($sql);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE users DROP COLUMN locale;
        SQL;

        $database->exec($sql);

        return true;
    }
}
