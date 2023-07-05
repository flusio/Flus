<?php

namespace flusio\migrations;

class Migration202007230001CreateMessages
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE messages (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                content TEXT NOT NULL,
                link_id TEXT REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE INDEX idx_messages_link_id ON messages(link_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_messages_link_id;
            DROP TABLE messages;
        SQL);

        return true;
    }
}
