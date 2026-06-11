<?php

namespace App\migrations;

class Migration202606110001CreateUrlStatuses
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE url_statuses (
                id BIGSERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
                url_hash TEXT NOT NULL,

                read_at TIMESTAMPTZ DEFAULT NULL,
                read_later_at TIMESTAMPTZ DEFAULT NULL,
                dismissed_at TIMESTAMPTZ DEFAULT NULL
            );

            CREATE UNIQUE INDEX idx_url_statuses_user_id_url_hash ON url_statuses(user_id, url_hash);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_url_statuses_user_id_url_hash;
            DROP TABLE url_statuses;
        SQL);

        return true;
    }
}
