<?php

namespace flusio\migrations;

class Migration202005181700AddLocaleToUser
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE users
            ADD COLUMN locale TEXT NOT NULL DEFAULT 'en_GB';
        SQL;

        $database->exec($sql);

        return true;
    }
}
