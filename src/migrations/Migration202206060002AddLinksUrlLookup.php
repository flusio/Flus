<?php

namespace flusio\migrations;

class Migration202206060002AddLinksUrlLookup
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN url_lookup TEXT GENERATED ALWAYS AS (simplify_url(url)) STORED;

            DROP INDEX idx_links_user_id_url;
            CREATE INDEX idx_links_user_id_url ON links(user_id, url_lookup);
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_user_id_url;

            ALTER TABLE links
            DROP COLUMN url_lookup;

            CREATE INDEX idx_links_user_id_url ON links(user_id, url);
        SQL);

        return true;
    }
}
