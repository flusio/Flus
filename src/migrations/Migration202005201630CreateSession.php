<?php

namespace flusio\migrations;

/**
 * @codeCoverageIgnore
 */
class Migration202005201630CreateSession
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE TABLE sessions (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                name TEXT NOT NULL,
                ip TEXT NOT NULL,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
                token TEXT UNIQUE REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE
            );

            CREATE INDEX idx_sessions_token ON sessions(token);
        SQL;

        $result = $database->exec($sql);
        if ($result === false) {
            $error_info = $database->errorInfo();
            throw new \Minz\Errors\DatabaseModelError(
                "Error in SQL statement: {$error_info[2]} ({$error_info[0]})."
            );
        }

        return true;
    }
}
