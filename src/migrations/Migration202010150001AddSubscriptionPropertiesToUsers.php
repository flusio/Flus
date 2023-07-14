<?php

namespace flusio\migrations;

class Migration202010150001AddSubscriptionPropertiesToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN subscription_account_id TEXT,
            ADD COLUMN subscription_expired_at TIMESTAMPTZ
                NOT NULL
                DEFAULT date_trunc('second', NOW() + INTERVAL '1 month');
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN subscription_account_id,
            DROP COLUMN subscription_expired_at;
        SQL);

        return true;
    }
}
