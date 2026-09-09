<?php

namespace App\migrations;

class Migration202609100001AddFullTextSearchIndexOnNotes
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE notes
            ADD COLUMN search_index TSVECTOR GENERATED ALWAYS AS (to_tsvector('french', content)) STORED;

            CREATE INDEX idx_notes_search ON notes USING GIN (search_index);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_notes_search;

            ALTER TABLE notes
            DROP COLUMN search_index;
        SQL);

        return true;
    }
}
