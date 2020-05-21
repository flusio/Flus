<?php

namespace flusio\migrations;

/**
 * @codeCoverageIgnore
 */
class Migration202005201530CreateIndexOnUsersEmail
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            CREATE INDEX idx_users_email ON users(email);
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
