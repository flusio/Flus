<?php

namespace tests;

use flusio\models;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InitializerHelper
{
    /** @var string */
    private static $tablenames;

    /**
     * Start a new transaction for the test
     *
     * @before
     */
    public function truncateTables()
    {
        $database = \Minz\Database::get();

        if (!self::$tablenames) {
            $statement = $database->query(<<<SQL
                SELECT tablename FROM pg_tables
                WHERE schemaname = ANY (current_schemas(false))
            SQL);
            $tables = array_column($statement->fetchAll(), 'tablename');
            self::$tablenames = implode(',', $tables);
        }

        $tablenames = self::$tablenames;
        $database->exec(<<<SQL
            TRUNCATE TABLE {$tablenames}
        SQL);
    }

    /**
     * @before
     */
    public function resetSession()
    {
        session_unset();
    }

    /**
     * @before
     */
    public function resetTestMailer()
    {
        \Minz\Tests\Mailer::clear();
    }
}
