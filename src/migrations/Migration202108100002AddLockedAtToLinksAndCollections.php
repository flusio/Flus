<?php

namespace flusio\migrations;

class Migration202108100002AddLockedAtToLinksAndCollections
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN locked_at TIMESTAMPTZ;

            ALTER TABLE collections
            ADD COLUMN locked_at TIMESTAMPTZ;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN locked_at;

            ALTER TABLE collections
            DROP COLUMN locked_at;
        SQL);

        return true;
    }
}
