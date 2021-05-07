<?php

namespace flusio\migrations;

class Migration202105070002DropNewsLinksUselessColumns
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            DROP COLUMN title,
            DROP COLUMN reading_time,
            DROP COLUMN image_filename;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links
            ADD COLUMN title TEXT,
            ADD COLUMN reading_time INTEGER NOT NULL DEFAULT 0,
            ADD COLUMN image_filename TEXT;

            UPDATE news_links nl
            SET title = l.title,
                reading_time = l.reading_time,
                image_filename = l.image_filename
            FROM links l
            WHERE nl.link_id = l.id;
        SQL);

        return true;
    }
}
