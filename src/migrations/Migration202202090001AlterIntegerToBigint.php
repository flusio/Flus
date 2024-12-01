<?php

namespace App\migrations;

class Migration202202090001AlterIntegerToBigint
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER SEQUENCE fetch_logs_id_seq AS BIGINT;
            ALTER TABLE fetch_logs ALTER id TYPE BIGINT;

            ALTER SEQUENCE links_to_collections_id_seq AS BIGINT;
            ALTER TABLE links_to_collections ALTER id TYPE BIGINT;

            ALTER SEQUENCE followed_collections_id_seq AS BIGINT;
            ALTER TABLE followed_collections ALTER id TYPE BIGINT;

            ALTER TABLE jobs ALTER number_attempts TYPE BIGINT;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER SEQUENCE fetch_logs_id_seq AS INTEGER;
            ALTER TABLE fetch_logs ALTER id TYPE INTEGER;

            ALTER SEQUENCE links_to_collections_id_seq AS INTEGER;
            ALTER TABLE links_to_collections ALTER id TYPE INTEGER;

            ALTER SEQUENCE followed_collections_id_seq AS INTEGER;
            ALTER TABLE followed_collections ALTER id TYPE INTEGER;

            ALTER TABLE jobs ALTER number_attempts TYPE INTEGER;
        SQL);

        return true;
    }
}
