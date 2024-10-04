<?php

namespace App\migrations;

class Migration202410040001AddIndexLinksUrl
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE EXTENSION IF NOT EXISTS pg_trgm;
            CREATE INDEX idx_links_url ON links USING gin (url gin_trgm_ops);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_url;
        SQL);

        return true;
    }
}
