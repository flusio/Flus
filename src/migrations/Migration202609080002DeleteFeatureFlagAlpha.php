<?php

namespace App\migrations;

class Migration202609080002DeleteFeatureFlagAlpha
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DELETE FROM feature_flags WHERE type = 'alpha';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        // do nothing on purpose
        return true;
    }
}
