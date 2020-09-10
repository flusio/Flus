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

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN in_news BOOLEAN NOT NULL DEFAULT false;

            UPDATE links SET in_news = true
            FROM news_links
            WHERE links.url = news_links.url
            AND links.user_id = news_links.user_id;
        SQL);

        return true;
    }
}
