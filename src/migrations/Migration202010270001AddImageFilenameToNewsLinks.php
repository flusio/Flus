<?php

namespace flusio\migrations;

class Migration202010270001AddImageFilenameToNewsLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            ADD COLUMN image_filename TEXT;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            DROP COLUMN image_filename;
        SQL);

        return true;
    }
}
