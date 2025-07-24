<?php

namespace App\migrations;

class Migration202507220001RenameMessagesToNotes
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_messages_link_id;
            ALTER TABLE messages RENAME TO notes;
            CREATE INDEX idx_notes_link_id ON notes(link_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_notes_link_id;
            ALTER TABLE notes RENAME TO messages;
            CREATE INDEX idx_messages_link_id ON messages(link_id);
        SQL);

        return true;
    }
}
