<?php

namespace App\migrations;

class Migration202404180001RenameJobsNamespaceFromFlusioToApp
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE jobs
            SET name = REPLACE(name, 'flusio\', 'App\')
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE jobs
            SET name = REPLACE(name, 'App\', 'flusio\')
        SQL);

        return true;
    }
}
