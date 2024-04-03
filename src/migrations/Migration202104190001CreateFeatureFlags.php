<?php

namespace App\migrations;

class Migration202104190001CreateFeatureFlags
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE feature_flags (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                type TEXT NOT NULL,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_feature_flags_type_user_id ON feature_flags(type, user_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_feature_flags_type_user_id;
            DROP TABLE feature_flags;
        SQL);

        return true;
    }
}
