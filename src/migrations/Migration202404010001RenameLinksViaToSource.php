<?php

namespace flusio\migrations;

class Migration202404010001RenameLinksViaToSource
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links RENAME COLUMN via_type TO source_type;
            ALTER TABLE links RENAME COLUMN via_resource_id TO source_resource_id;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links RENAME COLUMN source_type TO via_type;
            ALTER TABLE links RENAME COLUMN source_resource_id TO via_resource_id;
        SQL);

        return true;
    }
}
