<?php

namespace App\migrations;

class Migration202606100001MakeCollectionUserIdNullable
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ALTER COLUMN user_id DROP NOT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections
            ALTER COLUMN user_id SET NOT NULL;
        SQL);

        return true;
    }
}
