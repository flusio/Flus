<?php

namespace tests;

/**
 * Provide a way to count the SQL queries executed by a piece of code.
 *
 * It is useful to detect the "N+1 queries" problem, where the number of
 * queries grows with the number of rendered models instead of staying
 * constant.
 *
 *     list($response, $count_queries) = $this->countSqlQueries(function () {
 *         $response = $this->appRun('GET', '/');
 *         $response->render();
 *         return $response;
 *     });
 *
 * Beware that the templates are rendered lazily: the callback must call
 * Response::render() for the queries executed by the views to be counted.
 *
 * Only the queries going through Database::prepare() are counted.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait SqlQueriesHelper
{
    /**
     * Execute the given callback and return its result along with the number
     * of SQL queries that it executed.
     *
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return array{T, int}
     */
    protected function countSqlQueries(callable $callback): array
    {
        $database = \Minz\Database::get();

        $database->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [CountingStatement::class]);
        CountingStatement::$count = 0;

        try {
            $result = $callback();
        } finally {
            $database->setAttribute(\PDO::ATTR_STATEMENT_CLASS, [\PDOStatement::class]);
        }

        return [$result, CountingStatement::$count];
    }
}
