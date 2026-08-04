<?php

namespace App\models\dao;

use App\models;
use Minz\Database;

/**
 * Represent a collection of Flus in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Collection
{
    use BulkQueries;
    use MediaQueries;
    use collections\CleanerQueries;
    use collections\DiscoveryQueries;
    use collections\FetcherQueries;
    use collections\OpmlImportatorQueries;
    use collections\SearchQueries;
    use collections\StatisticsQueries;
    use Database\Lockable;

    /**
     * Return the list of collections attached to the given link.
     *
     * @return self[]
     */
    public static function listByLink(models\Link $link): array
    {
        return self::listByLinks([$link])[$link->id] ?? [];
    }

    /**
     * Return the list of collections attached to the given links, indexed by
     * the link ids.
     *
     * The links without any collection are absent from the returned array. A
     * collection attached to several links is represented by a single object,
     * shared by these links.
     *
     * @param models\Link[] $links
     * @param positive-int $chunk_size
     *
     * @return array<string, self[]>
     */
    public static function listByLinks(array $links, int $chunk_size = 1000): array
    {
        $link_ids = array_column($links, 'id');
        $link_ids = array_unique($link_ids);
        $link_ids = array_values($link_ids);

        $collections_by_ids = [];
        $collections_by_link_ids = [];

        foreach (array_chunk($link_ids, $chunk_size) as $chunk_link_ids) {
            $ids_as_question_marks = array_fill(0, count($chunk_link_ids), '?');
            $ids_where_statement = implode(', ', $ids_as_question_marks);

            $sql = <<<SQL
                SELECT lc.link_id, c.*
                FROM collections c, links_to_collections lc

                WHERE lc.collection_id = c.id
                AND lc.link_id IN ({$ids_where_statement})
                AND c.type = 'collection'
            SQL;

            $database = Database::get();
            $statement = $database->prepare($sql);
            $statement->execute($chunk_link_ids);

            foreach ($statement->fetchAll() as $row) {
                // link_id is not a column of Collection: it must be removed
                // from the row before the model is built from it.
                $link_id = strval($row['link_id']);
                unset($row['link_id']);

                $collection_id = strval($row['id']);
                $collection = $collections_by_ids[$collection_id] ?? self::fromDatabaseRow($row);
                $collections_by_ids[$collection_id] = $collection;

                $collections_by_link_ids[$link_id][] = $collection;
            }
        }

        return $collections_by_link_ids;
    }

    /**
     * Return the sources of the given links, indexed by their ids.
     *
     * The links without any source, and the sources that don't exist anymore,
     * are absent from the returned array.
     *
     * @param models\Link[] $links
     * @param positive-int $chunk_size
     *
     * @return array<string, self>
     */
    public static function listSourcesByLinks(array $links, int $chunk_size = 1000): array
    {
        $source_ids = array_column($links, 'source_id');
        $source_ids = array_filter($source_ids);
        $source_ids = array_unique($source_ids);
        $source_ids = array_values($source_ids);

        $sources_by_ids = [];

        foreach (array_chunk($source_ids, $chunk_size) as $chunk_source_ids) {
            $sources = self::listBy(['id' => $chunk_source_ids]);

            foreach ($sources as $source) {
                $sources_by_ids[$source->id] = $source;
            }
        }

        return $sources_by_ids;
    }

    /**
     * Return the collections of the given user with its computed properties.
     *
     * @param string $user_id
     *     The user id that the collections must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'private'?: bool,
     *     'count_hidden'?: bool,
     * } $options
     *
     * Description of the options:
     * - private (default to true), indicates if private collections must be
     *   included. If private are excluded and number_links property is
     *   selected, empty public collections are not returned either.
     * - count_hidden (default to true), indicates if hidden links must be
     *   counted
     *
     * @return self[]
     */
    public static function listComputedByUserId(
        string $user_id,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'private' => true,
            'count_hidden' => true,
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
        ];

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.*) AS number_links';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = c.id
            SQL;
            $group_by_clause = 'GROUP BY c.id';

            if (!$options['count_hidden']) {
                $number_links_clause = ', COUNT(l.*) AS number_links';

                $join_clause .= <<<SQL
                    \n
                    LEFT JOIN links l
                    ON lc.link_id = l.id
                    AND l.is_hidden = false
                SQL;
            }
        }

        $private_clause = '';
        $non_empty_clause = '';
        if (!$options['private']) {
            $private_clause = 'AND c.is_public = true';

            if (in_array('number_links', $selected_computed_props)) {
                $non_empty_clause = 'HAVING COUNT(lc.*) > 0';

                if (!$options['count_hidden']) {
                    $non_empty_clause = 'HAVING COUNT(l.*) > 0';
                }
            }
        }

        $sql = <<<SQL
            SELECT
                c.*
                {$number_links_clause}
            FROM collections c

            {$join_clause}

            WHERE c.user_id = :user_id
            AND c.type = 'collection'

            {$private_clause}

            {$group_by_clause}

            {$non_empty_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the collections followed by the given user with its computed properties.
     *
     * Only public collections are returned. That means if a user started to
     * follow a collection when it was public, if is_public is changed to
     * false, the user will not be able to see the collections anymore.
     *
     * @param string $user_id
     *     The id of the user who follows the collections.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'type'?: 'collection'|'feed'|'all',
     * } $options
     *
     * Description of the options:
     *
     * - type (default is 'all'), indicates what type of Collection must be
     *   returned. Count of links is optimized for "feed" collections so it may
     *   be interesting to call this method in two steps.
     *
     * @return self[]
     */
    public static function listComputedFollowedByUserId(
        string $user_id,
        array $selected_computed_props,
        array $options = []
    ): array {
        $default_options = [
            'type' => 'all',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
        ];

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.*) AS number_links';
            $group_by_clause = 'GROUP BY c.id, fc.group_id';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = fc.collection_id
            SQL;

            if ($options['type'] !== 'feed') {
                // Joinning the links table is only required if we need to
                // check the is_hidden property. Because feeds contain only
                // public links, we can optimize the request in this specific
                // case.
                $number_links_clause = ', COUNT(l.*) AS number_links';
                $join_clause .= <<<SQL
                    LEFT JOIN links l
                    ON lc.link_id = l.id
                    AND l.is_hidden = false
                SQL;
            }
        }

        $type_clause = '';
        if ($options['type'] === 'collection') {
            $type_clause = "AND type = 'collection'";
        } elseif ($options['type'] === 'feed') {
            $type_clause = "AND type = 'feed'";
        }

        $time_filter_clause = '';
        if (in_array('time_filter', $selected_computed_props)) {
            $time_filter_clause = ', fc.time_filter AS time_filter';
        }

        $sql = <<<SQL
            SELECT
                c.*,
                fc.group_id
                {$number_links_clause}
                {$time_filter_clause}
            FROM collections c, followed_collections fc

            {$join_clause}

            WHERE fc.collection_id = c.id
            AND fc.user_id = :user_id

            AND (
                c.is_public = true
                OR c.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                )
            )

            {$type_clause}

            {$group_by_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the collections shared to the given user with its computed properties.
     *
     * @param string $user_id
     *     The id of the user with who the collections are shared.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'access_type'?: 'any'|'read'|'write',
     * } $options
     *
     * Description of the options:
     *
     * - access_type (default is 'any'), indicates with which access the
     *   collections must have been shared.
     *
     * @return self[]
     */
    public static function listComputedSharedToUserId(
        string $user_id,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'access_type' => 'any',
        ];
        $options = array_merge($default_options, $options);

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.*) AS number_links';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = c.id
            SQL;
            $group_by_clause = 'GROUP BY c.id';
        }

        // we don't need the clause if access_type is 'any' (i.e. the type
        // doesn't matter) or 'read' (i.e. read access is included in write
        // access)
        $access_type_clause = '';
        if ($options['access_type'] === 'write') {
            $access_type_clause = "AND cs.type = 'write'";
        }

        $sql = <<<SQL
            SELECT
                c.*
                {$number_links_clause}
            FROM collection_shares cs, collections c

            {$join_clause}

            WHERE cs.collection_id = c.id
            AND cs.user_id = :user_id

            {$access_type_clause}

            {$group_by_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the collections shared by a user to another user with their computed properties.
     *
     * @param string $user_id
     *     The id of the user who shares the collections.
     * @param string $to_user_id
     *     The id of the user with who the collections are shared.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     *
     * @return self[]
     */
    public static function listComputedSharedByUserIdTo(
        string $user_id,
        string $to_user_id,
        array $selected_computed_props,
    ): array {
        $parameters = [
            ':user_id' => $user_id,
            ':to_user_id' => $to_user_id,
        ];

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.*) AS number_links';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = c.id
            SQL;
            $group_by_clause = 'GROUP BY c.id';
        }

        $sql = <<<SQL
            SELECT
                c.*
                {$number_links_clause}
            FROM collection_shares cs, collections c

            {$join_clause}

            WHERE c.user_id = :user_id
            AND c.type = 'collection'

            AND cs.collection_id = c.id
            AND cs.user_id = :to_user_id

            {$group_by_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * List the sources followed by the given user.
     *
     * The number_streams property is always computed: it counts the streams of
     * the user in which each source is present.
     *
     * @return self[]
     */
    public static function listSourcesByUser(models\User $user): array
    {
        $parameters = [
            ':user_id' => $user->id,
        ];

        $visibility_clause = self::buildVisibilityClause($user);

        $sql = <<<SQL
            SELECT c.*, (
                SELECT COUNT(*) FROM streams_to_follows sf
                WHERE sf.follow_id = fc.id
            ) AS number_streams
            FROM collections c, followed_collections fc

            WHERE fc.collection_id = c.id
            AND fc.user_id = :user_id

            {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * List the collections present in the given stream.
     *
     * Only the collections that the context user can view are returned, or
     * only the public ones if no context user is given.
     *
     * The number_streams property is always computed: it counts the streams of
     * the stream's owner in which each source is present.
     *
     * @param array{
     *     context_user?: ?models\User,
     * } $options
     *
     * @return self[]
     */
    public static function listByStream(models\Stream $stream, array $options = []): array
    {
        $context_user = $options['context_user'] ?? null;

        $parameters = [
            ':stream_id' => $stream->id,
        ];

        if ($context_user) {
            $parameters[':user_id'] = $context_user->id;
        }

        $visibility_clause = self::buildVisibilityClause($context_user);

        $sql = <<<SQL
            SELECT c.*, (
                SELECT COUNT(*) FROM streams_to_follows sf2
                WHERE sf2.follow_id = fc.id
            ) AS number_streams
            FROM collections c, followed_collections fc, streams_to_follows sf

            WHERE c.id = fc.collection_id
            AND sf.follow_id = fc.id
            AND sf.stream_id = :stream_id

            {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the sources of the given streams, indexed by stream id.
     *
     * Only the sources that the given user can view are returned. The streams
     * without viewable source are absent from the result.
     *
     * Contrary to listByStream(), the number_streams property is NOT computed:
     * this method is called on every page (see models\Stream::listByUser()) and
     * only needs to know which sources belong to which stream. Its results must
     * not be used where the property is expected, nor be injected in the
     * "sources" memoization of the Stream model.
     *
     * @param models\Stream[] $streams
     * @param array{
     *     context_user?: ?models\User,
     * } $options
     *
     * @return array<string, self[]>
     */
    public static function listByStreams(array $streams, array $options): array
    {
        if (!$streams) {
            return [];
        }

        $user = $options['context_user'] ?? null;

        $parameters = [];

        if ($user) {
            $parameters[':user_id'] = $user->id;
        }

        $stream_ids_placeholders = [];

        foreach (array_values($streams) as $index => $stream) {
            $placeholder = ":stream_id_{$index}";
            $stream_ids_placeholders[] = $placeholder;
            $parameters[$placeholder] = $stream->id;
        }

        /** @var literal-string */
        $stream_ids_statement = implode(', ', $stream_ids_placeholders);

        $visibility_clause = self::buildVisibilityClause($user);

        $sql = <<<SQL
            SELECT c.*, sf.stream_id
            FROM collections c, followed_collections fc, streams_to_follows sf

            WHERE c.id = fc.collection_id
            AND sf.follow_id = fc.id
            AND sf.stream_id IN ({$stream_ids_statement})

            {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $sources_by_stream_ids = [];

        foreach ($statement->fetchAll() as $row) {
            $stream_id = strval($row['stream_id']);
            unset($row['stream_id']);

            $sources_by_stream_ids[$stream_id][] = self::fromDatabaseRow($row);
        }

        return $sources_by_stream_ids;
    }

    /**
     * Return the clause limiting the collections (aliased as "c") to those
     * that the given user can view, or to the public ones if no user is given.
     *
     * The :user_id parameter must be bound when a user is given.
     *
     * @see \App\auth\CollectionsAccess::canView
     */
    private static function buildVisibilityClause(?models\User $user): string
    {
        if (!$user) {
            return 'AND c.is_public = true';
        }

        return <<<SQL
            AND (
                c.is_public = true
                OR c.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                )
            )
        SQL;
    }

    /**
     * Return whether the link is in a collection owned by the given user or not.
     */
    public static function existsForUserIdAndLinkId(string $user_id, string $link_id): bool
    {
        $sql = <<<'SQL'
            SELECT EXISTS (
                SELECT 1
                FROM collections c, links_to_collections lc

                WHERE c.id = lc.collection_id
                AND c.user_id = :user_id
                AND lc.link_id = :link_id
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':link_id' => $link_id,
        ]);
        return (bool) $statement->fetchColumn();
    }

    /**
     * List collections which are writable by the given user, containing a link
     * not owned by the given user and with the given URL.
     *
     * @return self[]
     */
    public static function listWritableContainingNotOwnedLinkWithUrl(string $user_id, string $url_hash): array
    {
        $sql = <<<'SQL'
            SELECT c.*
            FROM links_to_collections lc, links l, collections c

            WHERE lc.collection_id = c.id
            AND lc.link_id = l.id

            AND l.user_id IS DISTINCT FROM :user_id
            AND l.url_hash = :url_hash

            AND (
                c.user_id = :user_id OR EXISTS (
                    SELECT 1
                    FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                    AND cs.type = 'write'
                )
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':url_hash' => $url_hash,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
