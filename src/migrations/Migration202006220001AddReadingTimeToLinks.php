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
}
