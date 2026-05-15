<?php

namespace App\migrations;

class Migration202605140003AddOriginToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN origin TEXT NOT NULL DEFAULT '';

            CREATE INDEX idx_links_origin ON links(origin) WHERE origin != '';
        SQL);

        $statement = $database->prepare(<<<'SQL'
            UPDATE links
            SET origin = :base_url || '/p/' || source_resource_id
            WHERE source_type = 'user';
        SQL);

        $statement->execute([
            ':base_url' => \Minz\Url::baseUrl(),
        ]);

        $statement = $database->prepare(<<<'SQL'
            UPDATE links
            SET origin = :base_url || '/collections/' || source_resource_id
            WHERE source_type = 'collection';
        SQL);

        $statement->execute([
            ':base_url' => \Minz\Url::baseUrl(),
        ]);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_links_origin;

            ALTER TABLE links
            DROP COLUMN origin;
        SQL);

        return true;
    }
}
