<?php

namespace flusio\migrations;

class Migration202105200001CreateGroups
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE groups (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                name TEXT NOT NULL,
                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_groups_user_id_name ON groups(user_id, name);

            ALTER TABLE collections
            ADD COLUMN group_id TEXT REFERENCES groups ON DELETE SET NULL ON UPDATE CASCADE;

            ALTER TABLE followed_collections
            ADD COLUMN group_id TEXT REFERENCES groups ON DELETE SET NULL ON UPDATE CASCADE;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE collections DROP COLUMN group_id;
            ALTER TABLE followed_collections DROP COLUMN group_id;
            DROP INDEX idx_groups_user_id_name;
            DROP TABLE groups;
        SQL);

        return true;
    }
}
