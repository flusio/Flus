<?php

namespace flusio\migrations;

class Migration202210060001AddUrlRepliesToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN url_replies TEXT NOT NULL DEFAULT '';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN url_replies;
        SQL);

        return true;
    }
}
