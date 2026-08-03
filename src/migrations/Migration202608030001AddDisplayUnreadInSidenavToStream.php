<?php

namespace App\migrations;

class Migration202608030001AddDisplayUnreadInSidenavToStream
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE streams
            ADD COLUMN display_unread_in_sidenav BOOLEAN NOT NULL DEFAULT true;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE streams
            DROP COLUMN display_unread_in_sidenav;
        SQL);

        return true;
    }
}
