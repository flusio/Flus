<?php

namespace flusio\migrations;

class Migration202108300001AddIndexToLinksFetchedCode
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_links_fetched_code ON links(fetched_code);
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_fetched_code;
        SQL);

        return true;
    }
}
