<?php

namespace App\migrations;

class Migration202007100001AddDescriptionToCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE collections
            ADD COLUMN description TEXT NOT NULL DEFAULT '';
        SQL;

        $database->exec($sql);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE collections
            DROP COLUMN description;
        SQL;

        $database->exec($sql);

        return true;
    }
}
