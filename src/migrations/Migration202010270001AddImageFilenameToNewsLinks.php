<?php

namespace App\migrations;

class Migration202010270001AddImageFilenameToNewsLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            ADD COLUMN image_filename TEXT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            DROP COLUMN image_filename;
        SQL);

        return true;
    }
}
