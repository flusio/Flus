<?php

namespace flusio\migrations;

class Migration202111180001UpdateIndexLinksFetchedCode
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_fetched_code;
            CREATE INDEX idx_links_fetched_code ON links(fetched_code) WHERE fetched_code < 200 OR fetched_code >= 300;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_fetched_code;
            CREATE INDEX idx_links_fetched_code ON links(fetched_code);
        SQL);

        return true;
    }
}
