<?php

namespace App\migrations;

class Migration202502060001CreateCacheEntries
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE cache_entries (
                id BIGSERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                expired_at TIMESTAMPTZ NOT NULL,
                key TEXT NOT NULL,
                url TEXT NOT NULL,
                response_path TEXT NOT NULL
            );

            CREATE INDEX idx_cache_entries_key ON cache_entries USING hash(key);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_cache_entries_key;
            DROP TABLE cache_entries;
        SQL);

        return true;
    }
}
