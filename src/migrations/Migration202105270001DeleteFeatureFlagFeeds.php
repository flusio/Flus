<?php

namespace App\migrations;

class Migration202105270001DeleteFeatureFlagFeeds
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<SQL
            DELETE FROM feature_flags WHERE type = 'feeds';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        // do nothing on purpose
        return true;
    }
}
