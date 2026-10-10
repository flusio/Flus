<?php

namespace App\migrations;

class Migration202610100002AddIndexesUrlStatusesReadAtAndReadLaterAt
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_url_statuses_user_id_read_at
            ON url_statuses(user_id, read_at DESC)
            WHERE read_at IS NOT NULL;

            CREATE INDEX IF NOT EXISTS idx_url_statuses_user_id_read_later_at
            ON url_statuses(user_id, read_later_at DESC)
            WHERE read_later_at IS NOT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_url_statuses_user_id_read_later_at;
            DROP INDEX idx_url_statuses_user_id_read_at;
        SQL);

        return true;
    }
}
