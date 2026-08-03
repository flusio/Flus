<?php

namespace App\search_engine;

use App\models;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksSearcher
{
    /**
     * @param array{
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $pagination
     *
     * @return models\Link[]
     */
    public static function getLinks(
        models\User $user,
        Query $query,
        array $pagination = [],
    ): array {
        $default_pagination = [
            'offset' => 0,
            'limit' => 'ALL',
        ];

        $pagination = array_merge($default_pagination, $pagination);

        $parameters = [
            ':user_id' => $user->id,
            ':offset' => $pagination['offset'],
        ];

        $limit_statement = '';
        if ($pagination['limit'] !== 'ALL') {
            $limit_statement = 'LIMIT :limit';
            $parameters[':limit'] = $pagination['limit'];
        }

        list($query_statement, $query_parameters) = self::buildWhereQuery($query);
        $parameters = array_merge($parameters, $query_parameters);

        $sql = <<<SQL
            SELECT
                l.*,
                l.created_at AS published_at,
                (
                    SELECT COUNT(*) FROM notes n
                    WHERE n.link_id = l.id
                ) AS number_notes
            FROM links l

            WHERE l.user_id = :user_id

            {$query_statement}

            -- Exclude the links that are ONLY in the "never" collection
            AND NOT EXISTS (
                SELECT 1
                FROM links_to_collections lc, collections c

                WHERE lc.link_id = l.id
                AND lc.collection_id = c.id

                AND c.user_id = :user_id

                HAVING COUNT(CASE WHEN c.type='never' THEN 1 END) = 1
                AND COUNT(c.*) = 1
            )

            ORDER BY published_at DESC, l.id
            OFFSET :offset
            {$limit_statement}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return models\Link::fromDatabaseRows($statement->fetchAll());
    }

    public static function countLinks(models\User $user, Query $query): int
    {
        $parameters = [
            ':user_id' => $user->id,
        ];

        list($query_statement, $query_parameters) = self::buildWhereQuery($query);
        $parameters = array_merge($parameters, $query_parameters);

        $sql = <<<SQL
            SELECT COUNT(l.id)
            FROM links l

            WHERE l.user_id = :user_id

            {$query_statement}

            -- Exclude the links that are ONLY in the "never" collection
            AND NOT EXISTS (
                SELECT 1
                FROM links_to_collections lc, collections c

                WHERE lc.link_id = l.id
                AND lc.collection_id = c.id

                AND c.user_id = :user_id

                HAVING COUNT(CASE WHEN c.type='never' THEN 1 END) = 1
                AND COUNT(c.*) = 1
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return intval($statement->fetchColumn());
    }

    /**
     * Return the SQL conditions matching the given query, to be appended to
     * the WHERE clause of a request on the links table.
     *
     * The conditions all start with " AND ". `$alias` is the alias given to
     * the links table in the request.
     *
     * @param literal-string $alias
     *
     * @return array{literal-string, array<string, mixed>}
     */
    public static function buildWhereQuery(Query $query, string $alias = 'l'): array
    {
        $where_sql = '';
        $parameters = [];

        $textConditions = $query->getConditions('text');
        $textValues = array_map(function (Query\Condition $condition): string {
            return $condition->getValue();
        }, $textConditions);
        $textQuery = implode(' ', $textValues);

        if ($textQuery !== '') {
            $where_sql .= " AND {$alias}.search_index @@ plainto_tsquery('french', :query)";
            $parameters[':query'] = $textQuery;
        }

        $qualifierConditions = $query->getConditions('qualifier');

        foreach ($qualifierConditions as $condition) {
            $qualifier = $condition->getQualifier();
            if ($qualifier === 'url') {
                $value = $condition->getValue();

                $parameter_name = self::registerParameter($parameters, "%{$value}%");

                $where_sql .= " AND {$alias}.url ILIKE {$parameter_name}";
            }
        }

        $tagConditions = $query->getConditions('tag');

        $tags_parameters = [];
        $not_tags_parameters = [];

        foreach ($tagConditions as $condition) {
            $value = $condition->getValue();

            $parameter_name = self::registerParameter($parameters, mb_strtolower($value));

            if ($condition->not()) {
                $not_tags_parameters[] = $parameter_name;
            } else {
                $tags_parameters[] = $parameter_name;
            }
        }

        if ($tags_parameters) {
            $tags_statement = implode(',', $tags_parameters);
            $where_sql .= " AND {$alias}.tags ??& array[{$tags_statement}]";
        }

        if ($not_tags_parameters) {
            $not_tags_statement = implode(',', $not_tags_parameters);
            $where_sql .= " AND NOT ({$alias}.tags ??| array[{$not_tags_statement}])";
        }

        return [$where_sql, $parameters];
    }

    /**
     * Add a value to the list of the parameters and return the name under
     * which it is registered.
     *
     * The name is numbered so it doesn't conflict with the other parameters.
     *
     * @param array<string, mixed> $parameters
     *
     * @return literal-string
     */
    private static function registerParameter(array &$parameters, mixed $value): string
    {
        $parameter_name = ':search_param' . (count($parameters) + 1);

        $parameters[$parameter_name] = $value;

        // The name is built from a literal prefix and a counter: it never
        // contains anything coming from the query, but PHPStan cannot infer it.
        /** @phpstan-ignore return.type */
        return $parameter_name;
    }
}
