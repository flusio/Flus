<?php

namespace flusio\migrations;

class Migration202008270002MigrateOldNews
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            INSERT INTO news_links (created_at, title, url, reading_time, user_id)
            SELECT created_at, title, url, reading_time, user_id FROM links WHERE in_news = true;

            ALTER TABLE links DROP COLUMN in_news;
        SQL);

        return true;
    }
}
