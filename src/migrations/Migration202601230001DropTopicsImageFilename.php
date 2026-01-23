<?php

namespace App\migrations;

class Migration202601230001DropTopicsImageFilename
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_topics_image_filename;
            ALTER TABLE topics DROP COLUMN image_filename;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE topics ADD COLUMN image_filename TEXT;
            CREATE INDEX idx_topics_image_filename ON topics(image_filename) WHERE image_filename IS NOT NULL;
        SQL);

        return true;
    }
}
