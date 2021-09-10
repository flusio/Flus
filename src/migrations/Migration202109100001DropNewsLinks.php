<?php

namespace flusio\migrations;

class Migration202109100001DropNewsLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE news_links;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE news_links (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                published_at TIMESTAMPTZ,
                url TEXT NOT NULL,
                link_id TEXT REFERENCES links ON DELETE SET NULL ON UPDATE CASCADE,
                via_type TEXT NOT NULL DEFAULT '',
                via_collection_id TEXT REFERENCES collections ON DELETE SET NULL ON UPDATE CASCADE,
                read_at TIMESTAMPTZ,
                removed_at TIMESTAMPTZ,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );
        SQL);

        return true;
    }
}
