<?php

namespace flusio\migrations;

class Migration202210060001AddUrlRepliesToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN url_replies TEXT NOT NULL DEFAULT '';
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN url_replies;
        SQL);

        return true;
    }
}
