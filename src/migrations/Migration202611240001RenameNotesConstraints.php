<?php

namespace App\migrations;

class Migration202611240001RenameNotesConstraints
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        // The notes table was formerly named "messages": the instances
        // installed before its renaming kept the old names of its constraints.
        $statement = $database->query(<<<'SQL'
            SELECT conname FROM pg_constraint
            WHERE conrelid = 'notes'::regclass
            AND conname LIKE 'messages\_%'
        SQL);
        /** @var string[] $old_names */
        $old_names = $statement->fetchAll(\PDO::FETCH_COLUMN);

        foreach ($old_names as $old_name) {
            $name = substr($old_name, strlen('messages_'));
            $new_name = "notes_{$name}";
            $database->exec("ALTER TABLE notes RENAME CONSTRAINT {$old_name} TO {$new_name}");
        }

        return true;
    }

    public function rollback(): bool
    {
        // Do nothing on purpose.
        return true;
    }
}
