<?php

namespace App\migrations;

class Migration202608140001CreateViews
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE views (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                name TEXT NOT NULL,
                parameters JSON NOT NULL,
                is_default BOOLEAN NOT NULL DEFAULT false,

                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
                stream_id TEXT REFERENCES streams ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE INDEX idx_views_user_id ON views(user_id);
            CREATE INDEX idx_views_stream_id ON views(stream_id) WHERE stream_id IS NOT NULL;
            CREATE UNIQUE INDEX idx_views_default ON views(stream_id) WHERE is_default;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_views_default;
            DROP INDEX idx_views_stream_id;
            DROP INDEX idx_views_user_id;

            DROP TABLE views;
        SQL);

        return true;
    }
}
