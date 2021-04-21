<?php

namespace flusio\migrations;

class Migration202104200001AddUrlFeedsToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN url_feeds JSON NOT NULL DEFAULT '[]';
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN url_feeds;
        SQL);

        return true;
    }
}
