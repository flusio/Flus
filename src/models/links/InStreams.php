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

        $sources = self::listStreamSources($stream, $options);

        if (!$sources) {
            return [];
        }

        $user_of_statuses = $options['status'] !== 'all' ? $options['context_user'] : null;
        list($sql_join, $parameters) = self::buildStreamJoin($user_of_statuses);
        list($sql_where, $where_parameters) = self::buildStreamWhere($sources, $options);
        $parameters = array_merge($parameters, $where_parameters);

        $sql = <<<SQL
            SELECT l.*, lc.created_at AS published_at, lc.collection_id AS source_id, true AS group_by_source
            FROM links_to_collections lc

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

        $sources = self::listStreamSources($stream, $options);

        if (!$sources) {
            return [];
        }

        $user_of_statuses = $options['context_user'];
        list($sql_join, $parameters) = self::buildStreamJoin($user_of_statuses);
        list($sql_where, $where_parameters) = self::buildStreamWhere($sources, $options);
        $parameters = array_merge($parameters, $where_parameters);

        $parameters[':timezone'] = date_default_timezone_get();

        if ($user_of_statuses) {
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
            FROM links_to_collections lc

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

        $sources = self::listStreamSources($stream, $options);

        if (!$sources) {
            return [];
        }

        $user_of_statuses = $options['status'] !== 'all' ? $options['context_user'] : null;
        list($sql_join, $parameters) = self::buildStreamJoin($user_of_statuses);
        list($sql_where, $where_parameters) = self::buildStreamWhere($sources, $options);
        $parameters = array_merge($parameters, $where_parameters);

        $sql = <<<SQL
            SELECT lc.collection_id AS source_id, COUNT(l.id) AS count_all
            FROM links_to_collections lc

            {$sql_join}

            {$sql_where}

            GROUP BY lc.collection_id
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
     * Return the sources of the stream to consider, i.e. the collections that
     * the context user can view, limited to the selected source if there is
     * one.
     *
     * The sources are memoized by the Stream model: the different queries of a
     * same page share a single request to the database.
     *
     * @param array{
     *     context_user: ?User,
     *     source: ?Collection,
     * } $options
     *
     * @return Collection[]
     */
    private static function listStreamSources(Stream $stream, array $options): array
    {
        $sources = $stream->sources([
            'context_user' => $options['context_user'],
        ]);

        $selected_source = $options['source'];

        if ($selected_source) {
            // The selected source can come straight from a request parameter,
            // without being checked (see forms\traits\StreamLinks::links()):
            // it must be one of the sources that the user can view.
            $source_ids = array_column($sources, 'id');
            if (!in_array($selected_source->id, $source_ids, strict: true)) {
                return [];
            }

            return [$selected_source];
        }

        return $sources;
    }

    /**
     * Return the joins to add to the queries over the links of a stream.
     *
     * The url_statuses are joined only if a user is given. The :user_id
     * parameter is then returned along the statement.
     *
     * @return array{literal-string, array<string, mixed>}
     */
    private static function buildStreamJoin(?User $user_of_statuses): array
    {
        $parameters = [];

        $sql_join = <<<SQL
            INNER JOIN links l ON lc.link_id = l.id
        SQL;

        if ($user_of_statuses) {
            $parameters[':user_id'] = $user_of_statuses->id;

            $sql_join .= <<<SQL
                LEFT JOIN url_statuses us ON us.user_id = :user_id AND us.url_hash = l.url_hash
            SQL;
        }

        return [$sql_join, $parameters];
    }

    /**
     * Return the where clause to limit the links to the ones of a stream.
     *
     * The visibility of the sources is not checked here: it is already handled
     * by listStreamSources(), which only returns the collections that the
     * context user can view.
     *
     * @param Collection[] $sources
     * @param array{
     *     context_user: ?User,
     *     at: \DateTimeImmutable,
     *     days: int,
     *     status: string,
     *     query: ?Query,
     *     created_before: ?\DateTimeImmutable,
     * } $options
     *
     * @return array{literal-string, array<string, mixed>}
     */
    private static function buildStreamWhere(array $sources, array $options): array
    {
        $parameters = [];

        // Create the clause limiting the links to the sources of the stream.
        $source_ids_placeholders = [];

        foreach (array_values($sources) as $index => $source) {
            $placeholder = ":source_id_{$index}";
            $source_ids_placeholders[] = $placeholder;
            $parameters[$placeholder] = $source->id;
        }

        /** @var literal-string */
        $source_ids_statement = implode(', ', $source_ids_placeholders);

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

        $sql_where = <<<SQL
            WHERE lc.collection_id IN ({$source_ids_statement})
            AND l.is_hidden = false
            AND lc.created_at >= :at_start AND lc.created_at <= :at_end

            {$status_clause}
            {$search_clause}
            {$created_before_clause}
        SQL;

        return [$sql_where, $parameters];
    }
}
