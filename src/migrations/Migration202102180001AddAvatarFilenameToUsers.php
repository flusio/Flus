<?php

namespace App\migrations;

class Migration202102180001AddAvatarFilenameToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users ADD COLUMN avatar_filename TEXT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users DROP COLUMN avatar_filename;
        SQL);

        return true;
    }
}
