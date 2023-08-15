<?php

namespace flusio\migrations;

class Migration202308110001CreateMastodonTables
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE mastodon_servers (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                host TEXT NOT NULL,
                client_id TEXT NOT NULL,
                client_secret TEXT NOT NULL
            );

            CREATE TABLE mastodon_accounts (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                username TEXT NOT NULL,
                access_token TEXT NOT NULL,
                options JSON NOT NULL,

                mastodon_server_id INT NOT NULL REFERENCES mastodon_servers ON DELETE CASCADE ON UPDATE CASCADE,
                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP TABLE mastodon_accounts;
            DROP TABLE mastodon_servers;
        SQL);

        return true;
    }
}
