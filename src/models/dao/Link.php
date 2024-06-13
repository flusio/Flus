<?php

namespace App\models\dao;

use Minz\Database;
use App\models;

/**
 * Represent a link in database.
 *
 * @phpstan-import-type DatabaseCriteria from Database\Recordable
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Link
{
    use BulkQueries;
    use MediaQueries;
    use links\CleanerQueries;
    use links\DataExporterQueries;
    use links\FetcherQueries;
    use links\NewsQueries;
    use links\PocketQueries;
    use links\SearchQueries;
    use Database\Lockable;

    /**
     * Return a link with its computed properties.
     *
     * @param DatabaseCriteria $criteria
     *     The conditions the link must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     */
    public static function findComputedBy(array $criteria, array $selected_computed_props): ?self
    {
        // Note that publication date is usually computed by considering the
        // date of association with a collection. Without collection, we
        // consider its date of insertion in the database.
        $published_at_clause = '';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', l.created_at AS published_at';
        }

        $number_comments_clause = '';
        if (in_array('number_comments', $selected_computed_props)) {
            $number_comments_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM messages m
                    WHERE m.link_id = l.id
                ) AS number_comments
            SQL;
        }

        list($where_statement, $parameters) = Database\Helper::buildWhere($criteria);

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_comments_clause}
            FROM links l
            WHERE {$where_statement}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }

    /**
     * Return links of the given user with its computed properties.
     *
     * Links are sorted by published_at if the property is included, or by
     * created_at otherwise.
     *
     * Also, if unshared links are excluded, links are returned on the base of
     * their relation with collections. It means that published_at will be set
     * to the date of attachment of the related collection. If a link is
     * attached to multiple collections, it could potentially return the same
     * link several times with different published_at. However, the method
     * takes care of it and will return the link only once by taking the most
     * recent attachment.
     *
     * You may be affraid by this method and you would be right. This is the
     * price to pay to return not duplicated and ordered links with their
     * computed properties.
     *
     * @param string $user_id
     *     The user id the links must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'unshared'?: bool,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * Description of the options:
     *
     * - unshared (default to true), indicates if unshared links must be
     *   included. Shared links are visible and are included in one public
     *   collection at least.
     * - offset (default to 0), the offset for pagination
     * - limit (default to 'ALL') the limit for pagination
     *
     * @return self[]
     */
    public static function listComputedByUserId(
        string $user_id,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'unshared' => true,
            'offset' => 0,
            'limit' => 'ALL',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
            ':offset' => $options['offset'],
        ];

        $published_at_clause = '';
        $order_by_clause = 'ORDER BY l.created_at DESC, l.id';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', l.created_at AS published_at';
            $order_by_clause = 'ORDER BY published_at DESC, l.id';
        }

        $number_comments_clause = '';
        if (in_array('number_comments', $selected_computed_props)) {
            $number_comments_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM messages m
                    WHERE m.link_id = l.id
                ) AS number_comments
            SQL;
        }

        $visibility_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (!$options['unshared']) {
            $visibility_clause = 'AND l.is_hidden = false';
            $join_clause = <<<SQL
                INNER JOIN links_to_collections lc
                ON lc.link_id = l.id

                INNER JOIN collections c
                ON lc.collection_id = c.id
                AND c.is_public = true
            SQL;

            if (in_array('published_at', $selected_computed_props)) {
                $published_at_clause = ', MAX(lc.created_at) AS published_at';
                $group_by_clause = 'GROUP BY l.id';
            }
        }

        $limit_clause = '';
        if ($options['limit'] !== 'ALL') {
            $limit_clause = 'LIMIT :limit';
            $parameters[':limit'] = $options['limit'];
        }

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_comments_clause}
            FROM links l

            {$join_clause}

            WHERE l.user_id = :user_id

            {$visibility_clause}

            {$group_by_clause}
            {$order_by_clause}
            OFFSET :offset
            {$limit_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return links of the given collection with its computed properties.
     *
     * Links are sorted by published_at if the property is included, or by
     * created_at otherwise.
     *
     * @param string $collection_id
     *     The collection id the links must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'published_date'?: ?\DateTimeImmutable,
     *     'source'?: ?string,
     *     'hidden'?: bool,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * Description of the options:
     *
     * - published_date (default to null), limits the selection to the given publication date
     * - source (default to null), limits the selection to the given source (e.g. collection#1234567890)
     * - hidden (default to true), indicates if hidden links must be included
     * - offset (default to 0), the offset for pagination
     * - limit (default to 'ALL') the limit for pagination
     *
     * @return self[]
     */
    public static function listComputedByCollectionId(
        string $collection_id,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'published_date' => null,
            'source' => null,
            'hidden' => true,
            'offset' => 0,
            'limit' => 'ALL',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':collection_id' => $collection_id,
            ':offset' => $options['offset'],
        ];

        $published_at_clause = '';
        $order_by_clause = 'ORDER BY l.created_at DESC, l.id';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', lc.created_at AS published_at';
            $order_by_clause = 'ORDER BY lc.created_at DESC, l.id';
        }

        $number_comments_clause = '';
        if (in_array('number_comments', $selected_computed_props)) {
            $number_comments_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM messages m
                    WHERE m.link_id = l.id
                ) AS number_comments
            SQL;
        }

        $date_clause = '';
        if ($options['published_date'] !== null) {
            $date_clause = "AND date_trunc('day', lc.created_at) = :published_date";
            $parameters[':published_date'] = $options['published_date']->format('Y-m-d');
        }

        $source = $options['source'];
        $source_clause = '';
        if ($source && str_contains($source, '#')) {
            list($source_type, $source_id) = explode('#', $source, 2);
            $source_clause = 'AND source_type = :source_type AND source_resource_id = :source_id';
            $parameters[':source_type'] = $source_type;
            $parameters[':source_id'] = $source_id;
        }

        $visibility_clause = '';
        if (!$options['hidden']) {
            $visibility_clause = 'AND l.is_hidden = false';
        }

        $limit_clause = '';
        if ($options['limit'] !== 'ALL') {
            $limit_clause = 'LIMIT :limit';
            $parameters[':limit'] = $options['limit'];
        }

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_comments_clause}
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id
            {$date_clause}
            {$source_clause}
            {$visibility_clause}

            {$order_by_clause}
            OFFSET :offset
            {$limit_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Count links of the given collection.
     *
     * @param string $collection_id
     *     The collection id the links must match.
     * @param array{
     *     'hidden'?: bool,
     * } $options
     *
     * Description of the options:
     *
     * - hidden (default to true), indicates if hidden links must be included
     */
    public static function countByCollectionId(string $collection_id, array $options = []): int
    {
        $default_options = [
            'hidden' => true,
        ];
        $options = array_merge($default_options, $options);

        $visibility_clause = '';
        if (!$options['hidden']) {
            $visibility_clause = 'AND l.is_hidden = false';
        }

        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);

        return intval($statement->fetchColumn());
    }

    /**
     * Return whether or not the given user id has the link URL in its
     * bookmarks.
     */
    public static function isUrlInBookmarksOfUserId(string $user_id, string $url): bool
    {
        $sql = <<<'SQL'
            SELECT 1
            FROM links l, collections c, links_to_collections lc

            WHERE l.user_id = :user_id
            AND l.url_hash = :url_hash

            AND c.type = 'bookmarks'

            AND lc.collection_id = c.id
            AND lc.link_id = l.id;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':url_hash' => models\Link::hashUrl($url),
        ]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * Return whether or not the given user id read the link URL.
     */
    public static function isUrlReadByUserId(string $user_id, string $url): bool
    {
        $sql = <<<'SQL'
            SELECT 1
            FROM links l, collections c, links_to_collections lc

            WHERE l.user_id = :user_id
            AND l.url_hash = :url_hash

            AND c.type = 'read'

            AND lc.collection_id = c.id
            AND lc.link_id = l.id;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':url_hash' => models\Link::hashUrl($url),
        ]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * Find a link by its URL and collection id but not owned by the given user.
     */
    public static function findNotOwnedByCollectionIdAndUrl(
        string $user_id,
        string $collection_id,
        string $url_hash,
    ): ?self {
        $sql = <<<SQL
            SELECT l.*
            FROM links l, links_to_collections lc

            WHERE lc.link_id = l.id
            AND lc.collection_id = :collection_id

            AND l.url_hash = :url_hash
            AND l.user_id != :user_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':collection_id' => $collection_id,
            ':url_hash' => $url_hash,
        ]);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }

    /**
     * Return an estimated number of links.
     *
     * This method have better performance than basic count but is less
     * precise.
     *
     * @see https://wiki.postgresql.org/wiki/Count_estimate
     */
    public static function countEstimated(): int
    {
        $table_name = self::tableName();

        $sql = <<<SQL
            SELECT reltuples AS count
            FROM pg_class
            WHERE relname = ?;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$table_name]);
        return intval($statement->fetchColumn());
    }
}
