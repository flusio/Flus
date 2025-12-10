<?php

namespace App\migrations;

class Migration202512200001DropNoteIdFromMastodonStatuses
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_mastodon_statuses_note_id;

            ALTER TABLE mastodon_statuses
            DROP COLUMN note_id;
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE mastodon_statuses
            ADD COLUMN note_id TEXT REFERENCES notes ON DELETE CASCADE ON UPDATE CASCADE;

            CREATE INDEX idx_mastodon_statuses_note_id ON mastodon_statuses(note_id);
        SQL);

        return true;
    }
}
