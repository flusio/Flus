<?php

namespace flusio\migrations;

/**
 * @codeCoverageIgnore
 */
class Migration202005111350CreateToken
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE TABLE tokens (
                token TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                expired_at TIMESTAMPTZ NOT NULL,
                type TEXT NOT NULL,
                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
                invalidated_at TIMESTAMPTZ
            );
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
