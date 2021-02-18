<?php

namespace flusio\migrations;

class Migration202102180001AddAvatarFilenameToUsers
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users ADD COLUMN avatar_filename TEXT;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users DROP COLUMN avatar_filename;
        SQL);

        return true;
    }
}
