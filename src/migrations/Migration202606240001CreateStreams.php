<?php

namespace App\migrations;

class Migration202606240001CreateStreams
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
                is_public BOOLEAN NOT NULL DEFAULT false,
                image_filename TEXT,

                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE INDEX idx_streams_user_id ON streams(user_id);
            CREATE INDEX idx_streams_image_filename ON streams(image_filename) WHERE image_filename IS NOT NULL;

            CREATE TABLE streams_to_follows (
                id BIGSERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                stream_id TEXT REFERENCES streams ON DELETE CASCADE ON UPDATE CASCADE,
                follow_id BIGINT REFERENCES followed_collections ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_streams_to_follows ON streams_to_follows(stream_id, follow_id);
            CREATE INDEX idx_streams_to_follows_follow_id ON streams_to_follows(follow_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_streams_to_follows;
            DROP INDEX idx_streams_to_follows_follow_id;

            DROP TABLE streams_to_follows;

            DROP INDEX idx_streams_user_id;
            DROP INDEX idx_streams_image_filename;

            DROP TABLE streams;
        SQL);

        return true;
    }
}
