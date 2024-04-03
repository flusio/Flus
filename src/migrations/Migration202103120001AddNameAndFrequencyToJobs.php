<?php

namespace App\migrations;

class Migration202103120001AddNameAndFrequencyToJobs
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE jobs
            ADD COLUMN name TEXT NOT NULL DEFAULT '',
            ADD COLUMN frequency TEXT NOT NULL DEFAULT '';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE jobs
            DROP COLUMN name,
            DROP COLUMN frequency;
        SQL);

        return true;
    }
}
