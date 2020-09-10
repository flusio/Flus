<?php

namespace flusio\migrations;

class Migration202006220001AddReadingTimeToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE links
            ADD COLUMN reading_time INTEGER NOT NULL DEFAULT 0;
        SQL;

        $database->exec($sql);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE links
            DROP COLUMN reading_time;
        SQL;

        $database->exec($sql);

        return true;
    }
}
