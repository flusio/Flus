<?php

namespace flusio\migrations;

class Migration202105270001DeleteFeatureFlagFeeds
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<SQL
            DELETE FROM feature_flags WHERE type = 'feeds';
        SQL);

        return true;
    }

    public function rollback()
    {
        // do nothing on purpose
        return true;
    }
}
