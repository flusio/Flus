<?php

namespace App\models\links;

use App\models\Collection;
use App\models\Stream;
use App\models\User;
use App\search_engine\LinksSearcher;
use App\search_engine\Query;
use Minz\Database;

/**
 * Add methods to list the links published in the collections that a stream
 * contains.
 *
 * A stream is a selection of followed collections: the queries below depend on
 * the followed_collections table.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InStreams
{
    /**
     * Return the list of links of the given stream.
     *
     * @param array{
     *     context_user?: ?User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     *     source?: ?Collection,
     *     status?: string,
     *     query?: ?Query,
     *     created_before?: ?\DateTimeImmutable,
     * } $options
     *
     * @return self[]
     */
    public static function listByStream(Stream $stream, array $options): array
    {
        $default_options = [
            'context_user' => null,
            'at' => \Minz\Time::now(),
            'days' => 1,
            'source' => null,
            'status' => 'all',
            'query' => null,
            'created_before' => null,
        ];
        $options = array_merge($default_options, $options);

        $join_url_statuses = $options['context_user'] !== null && $options['status'] !== 'all';
        $sql_join = self::buildStreamJoin($join_url_statuses);
        list($sql_where, $parameters) = self::buildStreamWhere($stream, $options);

        $sql = <<<SQL
            SELECT l.*, lc.created_at AS published_at, lc.collection_id AS source_id, true AS group_by_source
            FROM streams_to_follows sf

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
     * Return the counts of links of the given stream, per day.
     *
     * The days are formatted as "Y-m-d", in the timezone of the application.
     * The days without links are not returned.
     *
     * The counts are given as a pair of the total number of links, and of the
     * number of unread links (always 0 if no context user is given).
     *
     * @param array{
     *     context_user?: ?User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     * } $options
     *
     * @return array<string, array{int, int}>
     */
    public static function countByStreamPerDay(Stream $stream, array $options): array
    {
        $default_options = [
            'context_user' => null,
            'at' => \Minz\Time::now(),
            'days' => 1,
        ];
        $options = array_merge($default_options, $options);
        // The counts per day are the ones of the whole activity of the stream:
        // they must not depend on the other filters.
        $options['source'] = null;
        $options['status'] = 'all';
        $options['query'] = null;
        $options['created_before'] = null;

        $join_url_statuses = $options['context_user'] !== null;
        $sql_join = self::buildStreamJoin($join_url_statuses);
        list($sql_where, $parameters) = self::buildStreamWhere($stream, $options);

        $parameters[':timezone'] = date_default_timezone_get();

        if ($join_url_statuses) {
            $sql_count_unread = <<<SQL
                COUNT(l.id) FILTER (
                    WHERE us.read_at IS NULL
                    AND us.read_later_at IS NULL
                    AND us.dismissed_at IS NULL
                )
            SQL;
        } else {
            $sql_count_unread = '0';
        }

        $sql = <<<SQL
            SELECT
                to_char(lc.created_at AT TIME ZONE :timezone, 'YYYY-MM-DD') AS day,
                COUNT(l.id) AS count_all,
                {$sql_count_unread} AS count_unread
            FROM streams_to_follows sf

            {$sql_join}

            {$sql_where}

            GROUP BY day
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[strval($row['day'])] = [intval($row['count_all']), intval($row['count_unread'])];
        }

        return $counts;
    }

    /**
     * Return the counts of links of the given stream, per source.
     *
     * The counts are the numbers of links matching the given dates, status and
     * query (the status is ignored if no context user is given).
     *
     * @param array{
     *     context_user?: ?User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     *     status?: string,
     *     query?: ?Query,
     * } $options
     *
     * @return array<string, int>
     */
    public static function countByStreamPerSource(Stream $stream, array $options): array
    {
        $default_options = [
            'context_user' => null,
            'at' => \Minz\Time::now(),
            'days' => 1,
            'status' => 'all',
            'query' => null,
        ];
        $options = array_merge($default_options, $options);
        $options['source'] = null;
        $options['created_before'] = null;

        $join_url_statuses = $options['context_user'] !== null && $options['status'] !== 'all';
        $sql_join = self::buildStreamJoin($join_url_statuses);
        list($sql_where, $parameters) = self::buildStreamWhere($stream, $options);

        $sql = <<<SQL
            SELECT c.id AS source_id, COUNT(l.id) AS count_all
            FROM streams_to_follows sf

            {$sql_join}

            {$sql_where}

            GROUP BY c.id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $counts = [];

        foreach ($statement->fetchAll() as $row) {
            $counts[strval($row['source_id'])] = intval($row['count_all']);
        }

        return $counts;
    }

    /**
     * @return literal-string
     */
    private static function buildStreamJoin(bool $join_url_statuses): string
    {
        $sql_join = <<<SQL
            INNER JOIN followed_collections fc ON sf.follow_id = fc.id
            INNER JOIN links_to_collections lc ON fc.collection_id = lc.collection_id
            INNER JOIN collections c ON fc.collection_id = c.id
            INNER JOIN links l ON lc.link_id = l.id
        SQL;

        if ($join_url_statuses) {
            $sql_join .= <<<SQL
                LEFT JOIN url_statuses us ON us.user_id = :user_id AND us.url_hash = l.url_hash
            SQL;
        }

        return $sql_join;
    }

    /**
     * @param array{
     *     context_user: ?User,
     *     at: \DateTimeImmutable,
     *     days: int,
     *     source: ?Collection,
     *     status: string,
     *     query: ?Query,
     *     created_before: ?\DateTimeImmutable,
     * } $options
     *
     * @return array{literal-string, array<string, mixed>}
     */
    private static function buildStreamWhere(Stream $stream, array $options): array
    {
        $parameters = [
            ':stream_id' => $stream->id,
        ];

        // Calculate the time span interval to get the links.
        $start = $options['at']->modify('00:00:00');
        $end = $start->modify('23:59:59');

        $days = min(max($options['days'], 1), 30);
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

        // Create the source clause to limit the links from the selected one.
        $source_clause = '';
        if ($options['source']) {
            $parameters[':source_id'] = $options['source']->id;

            $source_clause = 'AND c.id = :source_id';
        }

        // Create the search clause to limit the links matching the query.
        $search_clause = '';
        if ($options['query']) {
            list($search_clause, $search_parameters) = LinksSearcher::buildWhereQuery(
                $options['query'],
            );

            $parameters = array_merge($parameters, $search_parameters);
        }

        // Create the created_before clause to exclude the links added to the
        // database after the given date (i.e. links fetched in background
        // after the page was rendered).
        $created_before_clause = '';
        if ($options['created_before']) {
            $parameters[':created_before'] = $options['created_before']->format(Database\Column::DATETIME_FORMAT);

            $created_before_clause = 'AND l.created_at <= :created_before';
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
            AND l.is_hidden = false
            AND lc.created_at >= :at_start AND lc.created_at <= :at_end

            {$source_clause}
            {$status_clause}
            {$search_clause}
            {$created_before_clause}
            {$visibility_clause}
        SQL;

        return [$sql_where, $parameters];
    }
}
