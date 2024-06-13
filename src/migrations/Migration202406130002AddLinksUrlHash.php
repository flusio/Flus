<?php

namespace App\migrations;

class Migration202406130002AddLinksUrlHash
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE EXTENSION IF NOT EXISTS pgcrypto;

            ALTER TABLE links
            ADD COLUMN url_hash TEXT GENERATED ALWAYS AS (encode(digest(url, 'sha256'), 'hex')) STORED;

            DROP INDEX idx_links_user_id_url;
            DROP INDEX idx_links_url;

            ALTER TABLE links
            DROP COLUMN url_lookup;

            CREATE INDEX idx_links_user_id_url_hash ON links USING btree(user_id, url_hash);
            CREATE INDEX idx_links_url_hash ON links USING hash(url_hash);

            DROP FUNCTION simplify_url;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE FUNCTION simplify_url(url TEXT)
            RETURNS TEXT
            IMMUTABLE
            RETURNS NULL ON NULL INPUT
            AS $$ SELECT substring(url from 'https?://(.+)') $$
            LANGUAGE SQL;

            ALTER TABLE links
            ADD COLUMN url_lookup TEXT GENERATED ALWAYS AS (simplify_url(url)) STORED;

            DROP INDEX idx_links_user_id_url_hash;
            DROP INDEX idx_links_url_hash;

            ALTER TABLE links
            DROP COLUMN url_hash;

            CREATE INDEX idx_links_user_id_url ON links(user_id, url_lookup);
            CREATE INDEX idx_links_url ON links(url_lookup);

            DROP EXTENSION IF EXISTS pgcrypto;
        SQL);

        return true;
    }
}
