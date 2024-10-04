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

        $from_statement = 'links l';
        if (self::includeTextCondition($query)) {
            $from_statement .= ", plainto_tsquery('french', :query) AS query";
            $parameters[':query'] = '';
        }

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
                    SELECT COUNT(*) FROM messages m
                    WHERE m.link_id = l.id
                ) AS number_comments
            FROM {$from_statement}

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

        $from_statement = 'links l';
        if (self::includeTextCondition($query)) {
            $from_statement .= ", plainto_tsquery('french', :query) AS query";
            $parameters[':query'] = '';
        }

        list($query_statement, $query_parameters) = self::buildWhereQuery($query);
        $parameters = array_merge($parameters, $query_parameters);

        $sql = <<<SQL
            SELECT COUNT(l.id)
            FROM {$from_statement}

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
     * @return array{string, array<string, mixed>}
     */
    private static function buildWhereQuery(Query $query): array
    {
        $where_sql = '';
        $parameters = [];

        $textConditions = $query->getConditions('text');
        $textValues = array_map(function (Query\Condition $condition): string {
            return $condition->getValue();
        }, $textConditions);
        $textQuery = implode(' ', $textValues);

        if ($textQuery !== '') {
            $where_sql .= ' AND search_index @@ query';
            $parameters[':query'] = $textQuery;
        }

        $qualifierConditions = $query->getConditions('qualifier');

        foreach ($qualifierConditions as $condition) {
            $qualifier = $condition->getQualifier();
            if ($qualifier === 'url') {
                $value = $condition->getValue();

                $parameter_name = ':url' . (count($parameters) + 1);

                $where_sql .= " AND l.url ILIKE {$parameter_name}";

                $parameters[$parameter_name] = "%{$value}%";
            }
        }

        $tagConditions = $query->getConditions('tag');

        $tags_parameters = [];
        $not_tags_parameters = [];

        foreach ($tagConditions as $condition) {
            $value = $condition->getValue();

            $parameter_name = ':tag' . (count($parameters) + 1);

            $parameters[$parameter_name] = $value;

            if ($condition->not()) {
                $not_tags_parameters[] = $parameter_name;
            } else {
                $tags_parameters[] = $parameter_name;
            }
        }

        if ($tags_parameters) {
            $tags_statement = implode(',', $tags_parameters);
            $where_sql .= " AND l.tags::jsonb ??& array[{$tags_statement}]";
        }

        if ($not_tags_parameters) {
            $not_tags_statement = implode(',', $not_tags_parameters);
            $where_sql .= " AND NOT (l.tags::jsonb ??| array[{$not_tags_statement}])";
        }

        return [$where_sql, $parameters];
    }

    private static function includeTextCondition(Query $query): bool
    {
        $textConditions = $query->getConditions('text');
        return count($textConditions) > 0;
    }
}
