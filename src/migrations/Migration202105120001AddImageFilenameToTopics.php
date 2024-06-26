<?php

namespace App\migrations;

class Migration202105120001AddImageFilenameToTopics
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE topics
            ADD COLUMN image_filename TEXT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE topics
            DROP COLUMN image_filename;
        SQL);

        return true;
    }
}
