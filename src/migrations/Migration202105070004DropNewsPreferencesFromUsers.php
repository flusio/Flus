<?php

namespace flusio\migrations;

class Migration202105070004DropNewsPreferencesFromUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN news_preferences;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN news_preferences JSON NOT NULL DEFAULT '{}';
        SQL);

        return true;
    }
}
