<?php

namespace flusio\migrations;

class Migration202207200001CreateImageFilenameIndexes
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE INDEX idx_collections_image_filename ON collections(image_filename) WHERE image_filename IS NOT NULL;
            CREATE INDEX idx_links_image_filename ON links(image_filename) WHERE image_filename IS NOT NULL;
            CREATE INDEX idx_topics_image_filename ON topics(image_filename) WHERE image_filename IS NOT NULL;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_collections_image_filename;
            DROP INDEX idx_links_image_filename;
            DROP INDEX idx_topics_image_filename;
        SQL);

        return true;
    }
}
