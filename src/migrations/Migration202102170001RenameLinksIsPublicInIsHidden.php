<?php

namespace App\migrations;

class Migration202102170001RenameLinksIsPublicInIsHidden
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links RENAME COLUMN is_public TO is_hidden;
            UPDATE links SET is_hidden = NOT is_hidden;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links RENAME COLUMN is_hidden TO is_public;
            UPDATE links SET is_public = NOT is_public;
        SQL);

        return true;
    }
}
