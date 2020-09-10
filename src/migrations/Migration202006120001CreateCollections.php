<?php

namespace flusio\migrations;

class Migration202006120001CreateCollections
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE TABLE collections (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                name TEXT NOT NULL,
                type TEXT NOT NULL,
                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE INDEX idx_collections_user_id ON collections(user_id);
        SQL;

        $database->exec($sql);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            DROP INDEX idx_collections_user_id;
            DROP TABLE collections;
        SQL;

        $database->exec($sql);

        return true;
    }
}
