<?php

namespace App\migrations;

class Migration202510220001AddPublicationFrequencyPerYearToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ADD COLUMN publication_frequency_per_year INTEGER NOT NULL DEFAULT 0;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            DROP COLUMN publication_frequency_per_year;
        SQL);

        return true;
    }
}
