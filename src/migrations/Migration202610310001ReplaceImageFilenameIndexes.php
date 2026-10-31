<?php

namespace App\migrations;

class Migration202610310001ReplaceImageFilenameIndexes
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_collections_image_filename_pattern
            ON collections(image_filename text_pattern_ops)
            WHERE image_filename IS NOT NULL;

            CREATE INDEX IF NOT EXISTS idx_followed_collections_image_filename_pattern
            ON followed_collections(image_filename text_pattern_ops)
            WHERE image_filename IS NOT NULL;

            CREATE INDEX IF NOT EXISTS idx_links_image_filename_pattern
            ON links(image_filename text_pattern_ops)
            WHERE image_filename IS NOT NULL;

            CREATE INDEX IF NOT EXISTS idx_streams_image_filename_pattern
            ON streams(image_filename text_pattern_ops)
            WHERE image_filename IS NOT NULL;

            DROP INDEX IF EXISTS idx_collections_image_filename;
            DROP INDEX IF EXISTS idx_links_image_filename;
            DROP INDEX IF EXISTS idx_streams_image_filename;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_collections_image_filename ON collections(image_filename) WHERE image_filename IS NOT NULL;
            CREATE INDEX idx_links_image_filename ON links(image_filename) WHERE image_filename IS NOT NULL;
            CREATE INDEX idx_streams_image_filename ON streams(image_filename) WHERE image_filename IS NOT NULL;

            DROP INDEX idx_collections_image_filename_pattern;
            DROP INDEX idx_followed_collections_image_filename_pattern;
            DROP INDEX idx_links_image_filename_pattern;
            DROP INDEX idx_streams_image_filename_pattern;
        SQL);

        return true;
    }
}
