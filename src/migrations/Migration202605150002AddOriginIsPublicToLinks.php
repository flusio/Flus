<?php

namespace App\migrations;

class Migration202605150002AddOriginIsPublicToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN origin_is_public BOOLEAN NOT NULL DEFAULT false;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN origin_is_public;
        SQL);

        return true;
    }
}
