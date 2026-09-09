<?php

namespace App\migrations;

class Migration202609090003RenameNewsCollectionTypeToJournal
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE collections
            SET type = 'journal'
            WHERE type = 'news';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE collections
            SET type = 'news'
            WHERE type = 'journal';
        SQL);

        return true;
    }
}
