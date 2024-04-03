<?php

namespace App\migrations;

class Migration202104300002SetEmptyStringFetchedErrorToNull
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE links SET fetched_error = null
            WHERE fetched_error = '';

            UPDATE collections SET feed_fetched_error = null
            WHERE feed_fetched_error = '';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        // Do nothing on purpose
        return true;
    }
}
