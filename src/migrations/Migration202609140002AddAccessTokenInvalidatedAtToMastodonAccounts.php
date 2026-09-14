<?php

namespace App\migrations;

class Migration202609140002AddAccessTokenInvalidatedAtToMastodonAccounts
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE mastodon_accounts
            ADD COLUMN access_token_invalidated_at TIMESTAMPTZ;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE mastodon_accounts
            DROP COLUMN access_token_invalidated_at;
        SQL);

        return true;
    }
}
