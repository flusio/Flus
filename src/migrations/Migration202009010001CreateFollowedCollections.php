<?php

namespace flusio\migrations;

class Migration202009010001CreateFollowedCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE followed_collections (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
                collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_followed_collections ON followed_collections(user_id, collection_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_followed_collections;
            DROP TABLE followed_collections;
        SQL);

        return true;
    }
}
