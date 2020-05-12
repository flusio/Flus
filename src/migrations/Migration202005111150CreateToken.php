<?php

namespace flusio\migrations;

/**
 * @codeCoverageIgnore
 */
class Migration202005111150CreateToken
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE TABLE tokens (
                token TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                expired_at TIMESTAMPTZ NOT NULL,
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
