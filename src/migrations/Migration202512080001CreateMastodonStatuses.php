<?php

namespace App\migrations;

class Migration202512080001CreateMastodonStatuses
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE mastodon_statuses (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                content TEXT NOT NULL,
                status_id TEXT NOT NULL,
                posted_at TIMESTAMPTZ,

                mastodon_account_id INT REFERENCES mastodon_accounts ON DELETE CASCADE ON UPDATE CASCADE,
                reply_to_id TEXT REFERENCES mastodon_statuses ON DELETE CASCADE ON UPDATE CASCADE,
                link_id TEXT REFERENCES links ON DELETE CASCADE ON UPDATE CASCADE,
                note_id TEXT REFERENCES notes ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE INDEX idx_mastodon_statuses_posted_at ON mastodon_statuses(posted_at) WHERE posted_at IS NULL;
            CREATE INDEX idx_mastodon_statuses_mastodon_account_id ON mastodon_statuses(mastodon_account_id);
            CREATE INDEX idx_mastodon_statuses_reply_to_id ON mastodon_statuses(reply_to_id);
            CREATE INDEX idx_mastodon_statuses_link_id ON mastodon_statuses(link_id);
            CREATE INDEX idx_mastodon_statuses_note_id ON mastodon_statuses(note_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_mastodon_statuses_posted_at;
            DROP INDEX idx_mastodon_statuses_mastodon_account_id;
            DROP INDEX idx_mastodon_statuses_reply_to_id;
            DROP INDEX idx_mastodon_statuses_link_id;
            DROP INDEX idx_mastodon_statuses_note_id;
            DROP TABLE mastodon_statuses;
        SQL);

        return true;
    }
}
