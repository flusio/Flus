<?php

namespace App\migrations;

class Migration202605140001AddOptionFontFamilyToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN option_font_family TEXT NOT NULL DEFAULT 'default';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN option_font_family;
        SQL);

        return true;
    }
}
