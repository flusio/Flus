<?php

namespace flusio\migrations;

/**
 * @codeCoverageIgnore
 */
class Migration202005111330CreateUser
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE TABLE users (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                email TEXT UNIQUE NOT NULL,
                username TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                validated_at TIMESTAMPTZ,
                validation_token TEXT REFERENCES tokens ON DELETE SET NULL ON UPDATE CASCADE
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
