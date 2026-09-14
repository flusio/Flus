<?php

namespace App\migrations;

class Migration202609140001AddIndexLinksOrigin
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_links_origin ON links USING gin (origin gin_trgm_ops) WHERE origin != '';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_origin;
        SQL);

        return true;
    }
}
