<?php

namespace flusio\migrations;

class Migration202105070001RenameNewsLinksViaLinkIdInLinkId
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links RENAME COLUMN via_link_id TO link_id;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE news_links RENAME COLUMN link_id TO via_link_id;
        SQL);

        return true;
    }
}
