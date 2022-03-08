<?php

namespace flusio\migrations;

class Migration202203080001AnonymizeSessions
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE sessions
            SET name = '', ip = 'unknown';
        SQL);

        return true;
    }

    public function rollback()
    {
        return true;
    }
}
