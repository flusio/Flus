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
                l.created_at AS published_at
            FROM links l

            WHERE l.user_id = :user_id

            {$query_statement}

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
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return intval($statement->fetchColumn());
    }

    /**
     * Return a Query from the given string, accepting the qualifiers of the
     * given context: the links of a user, or the links of a stream.
     *
     * @throws SyntaxError
     *     Raised if the string cannot be parsed (see Query::fromString()).
     *
     * @param 'links'|'stream' $context
     */
    public static function buildQuery(string $queryString, string $context): Query
    {
        if ($context === 'stream') {
            $qualifiers = LinksQueryBuilder::STREAM_QUALIFIERS;
            $tags = false;
        } else {
            $qualifiers = LinksQueryBuilder::LINKS_QUALIFIERS;
            $tags = true;
        }

        return Query::fromString($queryString, $qualifiers, $tags);
    }

    /**
     * Return the SQL conditions matching the given query, to be appended to
     * the WHERE clause of a request on the links table (see LinksQueryBuilder::build()).
     *
     * `$alias` is the alias given to the links table in the request.
     *
     * @param literal-string $alias
     *
     * @return array{literal-string, array<string, mixed>}
     */
    public static function buildWhereQuery(Query $query, string $alias = 'l'): array
    {
        $queryBuilder = new LinksQueryBuilder($alias);
        return $queryBuilder->build($query);
    }
}
