<?php

namespace flusio\migrations;

class Migration202102100001CreateImportations
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE importations (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                type TEXT NOT NULL,
                status TEXT NOT NULL,
                options JSON NOT NULL,
                error TEXT NOT NULL DEFAULT '',
                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE importations;
        SQL);

        return true;
    }
}
