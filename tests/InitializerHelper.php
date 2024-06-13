<?php

namespace tests;

use App\models;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InitializerHelper
{
    private static ?string $tablenames = null;

    /**
     * Start a new transaction for the test
     */
    #[\PHPUnit\Framework\Attributes\Before]
    public function truncateTables(): void
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

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetSession(): void
    {
        session_unset();
    }

    #[\PHPUnit\Framework\Attributes\Before]
    public function resetTestMailer(): void
    {
        \Minz\Tests\Mailer::clear();
    }
}
