<?php

namespace tests;

/**
 * A PDO statement that counts the queries executed through it.
 *
 * It is not meant to be instantiated directly: it is set as the statement
 * class of the database connection by the SqlQueriesHelper trait.
 *
 * @see SqlQueriesHelper
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CountingStatement extends \PDOStatement
{
    public static int $count = 0;

    /**
     * @param ?array<array-key, mixed> $params
     */
    public function execute(?array $params = null): bool
    {
        self::$count += 1;

        return parent::execute($params);
    }
}
