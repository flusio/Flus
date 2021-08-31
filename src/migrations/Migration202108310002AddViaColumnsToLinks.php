<?php

namespace flusio\migrations;

use flusio\models;

class Migration202108310002AddViaColumnsToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN via_type TEXT NOT NULL DEFAULT '',
            ADD COLUMN via_link_id TEXT REFERENCES links ON DELETE SET NULL ON UPDATE CASCADE,
            ADD COLUMN via_collection_id TEXT REFERENCES collections ON DELETE SET NULL ON UPDATE CASCADE;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN via_type,
            DROP COLUMN via_link_id,
            DROP COLUMN via_collection_id;
        SQL);

        return true;
    }
}
