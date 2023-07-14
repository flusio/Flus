<?php

namespace flusio\migrations;

class Migration202008270001CreateNewsLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE news_links (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                title TEXT NOT NULL,
                url TEXT NOT NULL,
                reading_time INTEGER NOT NULL DEFAULT 0,
                is_hidden BOOLEAN NOT NULL DEFAULT false,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE news_links;
        SQL);

        return true;
    }
}
