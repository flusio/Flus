<?php

namespace App\migrations;

class Migration202609050001CreateStreamShares
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE stream_shares (
                id BIGSERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
                stream_id TEXT NOT NULL REFERENCES streams ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_stream_shares ON stream_shares(user_id, stream_id);
            CREATE INDEX idx_stream_shares_stream_id ON stream_shares(stream_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_stream_shares_stream_id;
            DROP INDEX idx_stream_shares;

            DROP TABLE stream_shares;
        SQL);

        return true;
    }
}
