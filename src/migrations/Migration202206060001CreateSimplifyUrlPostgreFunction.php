<?php

namespace App\migrations;

class Migration202206060001CreateSimplifyUrlPostgreFunction
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE FUNCTION simplify_url(url TEXT)
            RETURNS TEXT
            IMMUTABLE
            RETURNS NULL ON NULL INPUT
            AS $$ SELECT substring(url from 'https?://(.+)') $$
            LANGUAGE SQL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP FUNCTION simplify_url;
        SQL);

        return true;
    }
}
