<?php

namespace flusio\migrations;

/**
 * @codeCoverageIgnore
 */
class Migration202006180001AddFetchedToLinks
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $sql = <<<'SQL'
            ALTER TABLE links
            ADD COLUMN fetched_at TIMESTAMPTZ,
            ADD COLUMN fetched_code INTEGER NOT NULL DEFAULT 0,
            ADD COLUMN fetched_error TEXT;
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
