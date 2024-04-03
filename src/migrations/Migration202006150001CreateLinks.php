<?php

namespace App\migrations;

class Migration202006150001CreateLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE TABLE links (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                title TEXT NOT NULL,
                url TEXT NOT NULL,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_links_user_id_url ON links(user_id, url);

            CREATE TABLE links_to_collections (
                id SERIAL PRIMARY KEY,
                link_id TEXT REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
                collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_links_to_collections ON links_to_collections(link_id, collection_id);
            CREATE INDEX idx_links_to_collections_collection_id ON links_to_collections(collection_id);
        SQL;

        $database->exec($sql);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            DROP INDEX idx_links_to_collections_collection_id;
            DROP INDEX idx_links_to_collections;
            DROP TABLE links_to_collections;

            DROP INDEX idx_links_user_id_url;
            DROP TABLE links;
        SQL;

        $database->exec($sql);

        return true;
    }
}
