<?php

namespace App\migrations;

class Migration202205190001AddFullTextSearchIndexOnLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN search_index TSVECTOR GENERATED ALWAYS AS (to_tsvector('french', title || ' ' || url)) STORED;

            CREATE INDEX idx_links_search ON links USING GIN (search_index);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_search;

            ALTER TABLE links
            DROP COLUMN search_index;
        SQL);

        return true;
    }
}
