<?php

namespace flusio\migrations;

/**
 * @codeCoverageIgnore
 */
class Migration202006150001CreateLinks
{
    public function migrate()
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

        $result = $database->exec($sql);
        if ($result === false) {
            $error_info = $database->errorInfo();
            throw new \Minz\Errors\DatabaseModelError(
                "Error in SQL statement: {$error_info[2]} ({$error_info[0]})."
            );
        }

        return true;
    }
}
