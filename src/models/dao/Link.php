<?php

namespace App\models\dao;

use App\models;
use App\utils;
use Minz\Database;

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

        $number_notes_clause = '';
        if (in_array('number_notes', $selected_computed_props)) {
            $number_notes_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM notes m
                    WHERE m.link_id = l.id
                ) AS number_notes
            SQL;
        }

        list($where_statement, $parameters) = Database\Helper::buildWhere($criteria);

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_notes_clause}
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
     *     'tag'?: string,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * Description of the options:
     *
     * - unshared (default to true), indicates if unshared links must be
     *   included. Shared links are visible and are included in one public
     *   collection at least.
     * - tag, to filter links by the given tag.
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
            'tag' => '',
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

        $number_notes_clause = '';
        if (in_array('number_notes', $selected_computed_props)) {
            $number_notes_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM notes m
                    WHERE m.link_id = l.id
                ) AS number_notes
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
                AND c.user_id = :user_id
            SQL;

            if (in_array('published_at', $selected_computed_props)) {
                $published_at_clause = ', MAX(lc.created_at) AS published_at';
                $group_by_clause = 'GROUP BY l.id';
            }
        }

        $tag_clause = '';
        if ($options['tag']) {
            $tag_clause = 'AND l.tags ?? :tag';
            $parameters[':tag'] = mb_strtolower($options['tag']);
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
                {$number_notes_clause}
            FROM links l

            {$join_clause}

            WHERE l.user_id = :user_id

            {$visibility_clause}
            {$tag_clause}

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
     * Count links of the given user.
     *
     * @param string $user_id
     *     The user id the links must match.
     * @param array{
     *     'unshared'?: bool,
     *     'tag'?: string,
     * } $options
     *
     * Description of the options:
     *
     * - unshared (default to true), indicates if unshared links must be
     *   included. Shared links are visible and are included in one public
     *   collection at least.
     * - tag, to filter links by the given tag.
     */
    public static function countByUserId(
        string $user_id,
        array $options = [],
    ): int {
        $default_options = [
            'unshared' => true,
            'tag' => '',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
        ];

        $visibility_clause = '';
        $join_clause = '';
        if (!$options['unshared']) {
            $visibility_clause = 'AND l.is_hidden = false';
            $join_clause = <<<SQL
                INNER JOIN links_to_collections lc
                ON lc.link_id = l.id

                INNER JOIN collections c
                ON lc.collection_id = c.id
                AND c.is_public = true
                AND c.user_id = :user_id
            SQL;
        }

        $tag_clause = '';
        if ($options['tag']) {
            $tag_clause = 'AND l.tags ?? :tag';
            $parameters[':tag'] = mb_strtolower($options['tag']);
        }

        $sql = <<<SQL
            SELECT COUNT(distinct l.id)
            FROM links l

            {$join_clause}

            WHERE l.user_id = :user_id

            {$visibility_clause}
            {$tag_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return intval($statement->fetchColumn());
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
     * - source (default to null), limits the selection to the given source
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

        $number_notes_clause = '';
        if (in_array('number_notes', $selected_computed_props)) {
            $number_notes_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM notes m
                    WHERE m.link_id = l.id
                ) AS number_notes
            SQL;
        }

        $date_clause = '';
        if ($options['published_date'] !== null) {
            $date_clause = "AND lc.created_at >= :published_start AND lc.created_at <= :published_end";

            $start = $options['published_date']->modify('00:00:00');
            $end = $start->modify('23:59:59');

            $parameters[':published_start'] = $start->format(Database\Column::DATETIME_FORMAT);
            $parameters[':published_end'] = $end->format(Database\Column::DATETIME_FORMAT);
        }

        $source = $options['source'];
        $source_clause = '';
        if ($source) {
            $source_clause = 'AND source_id = :source';
            $parameters[':source'] = $source;
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
                {$number_notes_clause}
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
     * Return a list of suggested links for the user.
     *
     * Suggested links have the same URL as the given one, but are from
     * other users if they added notes to them.
     *
     * @return self[]
     */
    public static function listSuggestedFor(models\User $user, models\Link $link): array
    {
        $sql = <<<SQL
            SELECT l.* FROM links l

            -- Select the links with the same URL but not owned by the current
            -- user, and not the current link.
            WHERE l.url_hash = :url_hash
            AND l.user_id IS DISTINCT FROM :user_id
            AND l.id != :link_id

            AND EXISTS (
                -- Only if it's present in a collection...
                SELECT 1 FROM links_to_collections lc

                WHERE lc.link_id = l.id
                AND lc.collection_id IN (
                    -- ... owned by the user...
                    SELECT c.id FROM collections c WHERE c.user_id = :user_id
                    UNION
                    -- ... or shared with the user.
                    SELECT cs.collection_id FROM collection_shares cs WHERE cs.user_id = :user_id
                )
            )

            -- And only if there are notes attached to the links.
            AND EXISTS (
                SELECT 1 FROM notes n
                WHERE n.link_id = l.id
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':url_hash' => $link->url_hash,
            ':link_id' => $link->id,
            ':user_id' => $user->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Count links of the given collection.
     *
     * @param string $collection_id
     *     The collection id the links must match.
     * @param array{
     *     'hidden'?: bool,
     *     'since'?: \DateTimeImmutable,
     * } $options
     *
     * Description of the options:
     *
     * - hidden (default to true), indicates if hidden links must be included
     * - since (default to null), counts links that have been added since the
     *   given date only
     */
    public static function countByCollectionId(string $collection_id, array $options = []): int
    {
        $default_options = [
            'hidden' => true,
            'since' => null,
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':collection_id' => $collection_id,
        ];

        $visibility_clause = '';
        if (!$options['hidden']) {
            $visibility_clause = 'AND l.is_hidden = false';
        }

        $since_clause = '';
        if ($options['since']) {
            $since_clause = 'AND lc.created_at >= :since';
            $parameters[':since'] = $options['since']->format(Database\Column::DATETIME_FORMAT);
        }

        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            {$since_clause}

            {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return intval($statement->fetchColumn());
    }

    /**
     * Return the list of read later links of the given user.
     *
     * @return models\Link[]
     */
    public static function listReadLater(models\User $user, ?utils\Pagination $pagination): array
    {
        $parameters = [
            ':user_id' => $user->id,
        ];

        $pagination_clause = '';
        if ($pagination) {
            $pagination_clause = 'LIMIT :limit OFFSET :offset';
            $parameters[':limit'] = $pagination->numberPerPage();
            $parameters[':offset'] = $pagination->currentOffset();
        }

        $sql = <<<SQL
            SELECT l.*, us.read_later_at AS published_at
            FROM links l
            INNER JOIN url_statuses us ON l.url_hash = us.url_hash

            WHERE l.user_id = :user_id
            AND us.read_later_at IS NOT NULL

            ORDER BY published_at DESC, l.id

            {$pagination_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the count of read later links of the given user.
     */
    public static function countReadLater(models\User $user): int
    {
        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l
            INNER JOIN url_statuses us ON l.url_hash = us.url_hash

            WHERE l.user_id = :user_id
            AND us.read_later_at IS NOT NULL
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
        ]);

        return intval($statement->fetchColumn());
    }

    /**
     * Return the list of read links of the given user.
     *
     * @return models\Link[]
     */
    public static function listRead(models\User $user, ?utils\Pagination $pagination): array
    {
        $parameters = [
            ':user_id' => $user->id,
        ];

        $pagination_clause = '';
        if ($pagination) {
            $pagination_clause = 'LIMIT :limit OFFSET :offset';
            $parameters[':limit'] = $pagination->numberPerPage();
            $parameters[':offset'] = $pagination->currentOffset();
        }

        $sql = <<<SQL
            SELECT l.*, us.read_at AS published_at
            FROM links l
            INNER JOIN url_statuses us ON l.url_hash = us.url_hash

            WHERE l.user_id = :user_id
            AND us.read_at IS NOT NULL

            ORDER BY published_at DESC, l.id

            {$pagination_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the count of read links of the given user.
     */
    public static function countRead(models\User $user): int
    {
        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l
            INNER JOIN url_statuses us ON l.url_hash = us.url_hash

            WHERE l.user_id = :user_id
            AND us.read_at IS NOT NULL
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
        ]);

        return intval($statement->fetchColumn());
    }

    /**
     * Return the list of links of the given stream.
     *
     * @param array{
     *     context_user?: ?models\User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     *     status?: string,
     * } $options
     *
     * @return models\Link[]
     */
    public static function listByStream(models\Stream $stream, array $options): array
    {
        $default_options = [
            'context_user' => null,
            'at' => \Minz\Time::now(),
            'days' => 1,
            'status' => 'all',
        ];
        $options = array_merge($default_options, $options);

        $sql_join = self::buildStreamJoin($stream, $options);
        list($sql_where, $parameters) = self::buildStreamWhere($stream, $options);

        $sql = <<<SQL
            SELECT l.*, lc.created_at AS published_at, lc.collection_id AS source_id, true AS group_by_source
            FROM streams_to_follows sf, followed_collections fc, links_to_collections lc, collections c, links l

            {$sql_join}

            {$sql_where}

            ORDER BY published_at DESC, l.id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the count of links of the given stream.
     *
     * @param array{
     *     context_user?: ?models\User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     *     status?: string,
     * } $options
     */
    public static function countByStream(models\Stream $stream, array $options): int
    {
        $default_options = [
            'context_user' => null,
            'at' => \Minz\Time::now(),
            'days' => 1,
            'status' => 'all',
        ];
        $options = array_merge($default_options, $options);

        $sql_join = self::buildStreamJoin($stream, $options);
        list($sql_where, $parameters) = self::buildStreamWhere($stream, $options);

        $sql = <<<SQL
            SELECT COUNT(l.id)
            FROM streams_to_follows sf, followed_collections fc, links_to_collections lc, collections c, links l

            {$sql_join}

            {$sql_where}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return intval($statement->fetchColumn());
    }

    /**
     * @param array{
     *     context_user: ?models\User,
     *     at: \DateTimeImmutable,
     *     days: int,
     *     status: string,
     * } $options
     *
     * @return literal-string
     */
    private static function buildStreamJoin(models\Stream $stream, array $options): string
    {
        $sql_join = '';

        if (isset($options['context_user']) && $options['status'] !== 'all') {
            $sql_join .= <<<SQL
                LEFT JOIN url_statuses us ON us.user_id = :user_id AND us.url_hash = l.url_hash
            SQL;
        }

        return $sql_join;
    }

    /**
     * @param array{
     *     context_user: ?models\User,
     *     at: \DateTimeImmutable,
     *     days: int,
     *     status: string,
     * } $options
     *
     * @return array{literal-string, array<string, mixed>}
     */
    private static function buildStreamWhere(models\Stream $stream, array $options): array
    {
        $default_options = [
            'context_user' => null,
            'at' => \Minz\Time::now(),
            'days' => 1,
            'status' => 'all',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':stream_id' => $stream->id,
        ];

        // Calculate the time span interval to get the links.
        $start = $options['at']->modify('00:00:00');
        $end = $start->modify('23:59:59');

        $days = min(7, max(1, $options['days']));
        $days = $days - 1; // the actual interval is already of 1 day.
        if ($days > 0) {
            $start = $start->modify("-{$days} days");
        }

        $parameters[':at_start'] = $start->format(Database\Column::DATETIME_FORMAT);
        $parameters[':at_end'] = $end->format(Database\Column::DATETIME_FORMAT);

        // Create the status clause if status option is set.
        $status_clause = '';
        if ($options['context_user']) {
            if ($options['status'] === 'unread') {
                $status_clause = <<<SQL
                    AND (
                        us.read_at IS NULL
                        AND us.read_later_at IS NULL
                        AND us.dismissed_at IS NULL
                    )
                SQL;
            } elseif ($options['status'] === 'read') {
                $status_clause = 'AND us.read_at IS NOT NULL';
            } elseif ($options['status'] === 'read-later') {
                $status_clause = 'AND us.read_later_at IS NOT NULL';
            }
        }

        // Create the visibility clause, adapted if a context user is passed.
        $visibility_clause = 'AND (l.is_hidden = false AND c.is_public = true)';

        if ($options['context_user']) {
            $parameters[':user_id'] = $options['context_user']->id;

            $visibility_clause = <<<SQL
                AND (
                    (l.is_hidden = false AND c.is_public = true)
                    OR c.user_id = :user_id
                    OR EXISTS (
                        SELECT 1 FROM collection_shares cs
                        WHERE cs.user_id = :user_id
                        AND cs.collection_id = c.id
                    )
                )
            SQL;
        }

        $sql_where = <<<SQL
            WHERE sf.stream_id = :stream_id

            AND sf.follow_id = fc.id
            AND fc.collection_id = c.id
            AND fc.collection_id = lc.collection_id
            AND lc.link_id = l.id

            AND l.is_hidden = false

            AND lc.created_at >= :at_start AND lc.created_at <= :at_end

            {$status_clause}
            {$visibility_clause}
        SQL;

        return [$sql_where, $parameters];
    }

    public function numberCollectionsForUser(\App\models\User $user): int
    {
        $sql = <<<SQL
            SELECT COUNT(c.*)
            FROM collections c, links_to_collections lc, links l

            WHERE l.url_hash = :link_url_hash
            AND l.user_id = :user_id

            AND lc.link_id = l.id
            AND lc.collection_id = c.id

            AND c.type = 'collection'
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':link_url_hash' => $this->url_hash,
            ':user_id' => $user->id,
        ]);

        return intval($statement->fetchColumn());
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
            AND l.user_id IS DISTINCT FROM :user_id
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

    /**
     * Return the oldest publication date in the collection since the given date.
     */
    public static function getOldestPublicationDateSince(
        string $collection_id,
        \DateTimeImmutable $since
    ): ?\DateTimeImmutable {
        $sql = <<<SQL
            SELECT created_at
            FROM links_to_collections

            WHERE collection_id = :collection_id
            AND created_at >= :since

            ORDER BY created_at ASC
            LIMIT 1
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
            ':since' => $since->format(Database\Column::DATETIME_FORMAT),
        ]);

        $published_at = $statement->fetchColumn();

        if (!is_string($published_at)) {
            return null;
        }

        $published_at = \DateTimeImmutable::createFromFormat(
            Database\Column::DATETIME_FORMAT,
            $published_at,
        );

        if ($published_at === false) {
            return null;
        }

        return $published_at;
    }
}
