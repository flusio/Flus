<?php

namespace App\migrations;

class Migration202512100001AddStatusesMaxCharactersToMastodonServers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE mastodon_servers
            ADD COLUMN statuses_max_characters INT NOT NULL DEFAULT 500;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE mastodon_servers
            DROP COLUMN statuses_max_characters;
        SQL);

        return true;
    }
}
