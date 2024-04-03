<?php

namespace App\migrations;

class Migration202207280001CreateIndexLinksUrl
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_links_url ON links(url_lookup);
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
