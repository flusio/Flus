<?php

namespace flusio\migrations;

class Migration202108300002DropUniqueConstraintOfLinksUrl
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_user_id_url;
            CREATE INDEX idx_links_user_id_url ON links(user_id, url);
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_user_id_url;
            CREATE UNIQUE INDEX idx_links_user_id_url ON links(user_id, url);
        SQL);

        return true;
    }
}
