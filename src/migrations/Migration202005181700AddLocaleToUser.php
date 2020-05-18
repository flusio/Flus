<?php

namespace flusio\migrations;

/**
 * @codeCoverageIgnore
 */
class Migration202005181700AddLocaleToUser
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE users
            ADD COLUMN locale TEXT NOT NULL DEFAULT 'en_GB';
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
