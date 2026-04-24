<?php

namespace App\migrations;

class Migration202604240001CreateStreams
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE streams (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                name TEXT NOT NULL,
                description TEXT NOT NULL DEFAULT '',
                image_filename TEXT,
                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE INDEX idx_streams_user_id ON streams(user_id);
            CREATE INDEX idx_streams_image_filename ON streams(image_filename) WHERE image_filename IS NOT NULL;

            CREATE TABLE streams_to_collections (
                id BIGSERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                stream_id TEXT REFERENCES streams ON DELETE CASCADE ON UPDATE CASCADE,
                collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_streams_to_collections ON streams_to_collections(stream_id, collection_id);
            CREATE INDEX idx_streams_to_collections_collection_id ON streams_to_collections(collection_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_streams_to_collections;
            DROP INDEX idx_streams_to_collections_collection_id;
            DROP TABLE streams_to_collections;

            DROP INDEX idx_streams_user_id;
            DROP INDEX idx_streams_image_filename;
            DROP TABLE streams;
        SQL);

        return true;
    }
}
