<?php

namespace flusio\models\dao;

/**
 * Represent a link in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Link extends \Minz\DatabaseModel
{
    use BulkHelper;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Link::PROPERTIES);
        parent::__construct('links', 'id', $properties);
    }

    /**
     * Return link with computed number_comments
     *
     * @param array $values
     *
     * @return array
     */
    public function findByWithNumberComments($values)
    {
        $parameters = [];
        $where_statement_as_array = [];
        foreach ($values as $property => $parameter) {
            $parameters[] = $parameter;
            $where_statement_as_array[] = "{$property} = ?";
        }
        $where_statement = implode(' AND ', $where_statement_as_array);

        $sql = <<<SQL
            SELECT l.*, (
                SELECT COUNT(*) FROM messages m
                WHERE m.link_id = l.id
            ) AS number_comments
            FROM links l
            WHERE {$where_statement}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        $result = $statement->fetch();
        if ($result) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Return links within the given collection
     *
     * You can pass an offset and a limit to paginate the results. It is not
     * paginated by default.
     *
     * @param string $collection_id
     * @param boolean $visible_only
     * @param integer $offset
     * @param integer|string $limit
     *
     * @return array
     */
    public function listByCollectionIdWithNumberComments($collection_id, $visible_only, $offset = 0, $limit = 'ALL')
    {
        $values = [
            ':collection_id' => $collection_id,
            ':offset' => $offset,
        ];

        $visibility_clause = '';
        if ($visible_only) {
            $visibility_clause = 'AND l.is_hidden = false';
        }

        $limit_clause = '';
        if ($limit !== 'ALL') {
            $limit_clause = 'LIMIT :limit';
            $values[':limit'] = $limit;
        }

        $sql = <<<SQL
            SELECT l.*, lc.created_at AS published_at, (
                SELECT COUNT(m.*) FROM messages m
                WHERE m.link_id = l.id
            ) AS number_comments
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            {$visibility_clause}

            ORDER BY lc.created_at DESC, l.id
            OFFSET :offset
            {$limit_clause}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return links of the given user which have at least one comment.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listWithCommentsForUser($user_id)
    {
        $sql = <<<SQL
            SELECT l.*
            FROM links l, messages m

            WHERE l.id = m.link_id
            AND l.user_id = :user_id

            ORDER BY l.created_at DESC, l.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Count links within the given collection
     *
     * @param string $collection_id
     * @param boolean $visible_only
     *
     * @return array
     */
    public function countByCollectionId($collection_id, $visible_only)
    {
        $visibility_clause = '';
        if ($visible_only) {
            $visibility_clause = 'AND l.is_hidden = false';
        }

        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            {$visibility_clause}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);
        return intval($statement->fetchColumn());
    }

    /**
     * Return links listed in bookmarks of the given user, ordered randomly.
     *
     * @param string $user_id
     * @param integer|null $min_duration
     * @param integer|null $max_duration
     * @param \DateTime|null $until
     *
     * @return array
     */
    public function listFromBookmarksForNews($user_id, $min_duration, $max_duration, $until)
    {
        $where_placeholder = '';
        $values = [
            ':user_id' => $user_id,
        ];

        if ($min_duration !== null) {
            $where_placeholder .= 'AND l.reading_time >= :min_duration ';
            $values[':min_duration'] = $min_duration;
        }

        if ($max_duration !== null) {
            $where_placeholder .= 'AND l.reading_time < :max_duration ';
            $values[':max_duration'] = $max_duration;
        }

        if ($until !== null) {
            $where_placeholder .= 'AND lc.created_at >= :until ';
            $values[':until'] = $until->format(\Minz\Model::DATETIME_FORMAT);
        }

        $sql = <<<SQL
            SELECT l.*, lc.created_at AS published_at, 'bookmarks' AS via_type
            FROM links l, collections c, links_to_collections lc

            WHERE lc.link_id = l.id
            AND lc.collection_id = c.id

            AND c.user_id = :user_id
            AND l.user_id = :user_id

            AND c.type = 'bookmarks'

            {$where_placeholder}

            GROUP BY l.id, lc.created_at

            ORDER BY random()
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return public links listed in followed collections of the given user,
     * ordered by publication date. Links with a matching url in bookmarks or
     * read list are not returned.
     *
     * @param string $user_id
     * @param integer|null $min_duration
     * @param integer|null $max_duration
     * @param \DateTime|null $until
     *
     * @return array
     */
    public function listFromFollowedCollectionsForNews($user_id, $min_duration, $max_duration, $until)
    {
        $where_placeholder = '';
        $values = [
            ':user_id' => $user_id,
        ];

        if ($min_duration !== null) {
            $where_placeholder .= 'AND l.reading_time >= :min_duration ';
            $values[':min_duration'] = $min_duration;
        }

        if ($max_duration !== null) {
            $where_placeholder .= 'AND l.reading_time < :max_duration ';
            $values[':max_duration'] = $max_duration;
        }

        if ($until !== null) {
            $where_placeholder .= 'AND lc.created_at >= :until ';
            $values[':until'] = $until->format(\Minz\Model::DATETIME_FORMAT);
        }

        $sql = <<<SQL
            WITH excluded_links AS (
                SELECT l_exclude.url
                FROM links l_exclude, collections c_exclude, links_to_collections lc_exclude

                WHERE c_exclude.user_id = :user_id
                AND (c_exclude.type = 'bookmarks' OR c_exclude.type = 'read' OR c_exclude.type = 'never')

                AND lc_exclude.link_id = l_exclude.id
                AND lc_exclude.collection_id = c_exclude.id
            )

            SELECT l.*, lc.created_at AS published_at, 'followed' AS via_type, c.id AS via_collection_id
            FROM links l, collections c, links_to_collections lc, followed_collections fc

            WHERE fc.user_id = :user_id
            AND fc.collection_id = lc.collection_id

            AND lc.link_id = l.id
            AND lc.collection_id = c.id

            AND l.is_hidden = false
            AND c.is_public = true

            AND NOT EXISTS (SELECT 1 FROM excluded_links WHERE l.url = excluded_links.url)

            {$where_placeholder}

            GROUP BY l.id, lc.created_at, c.id

            ORDER BY lc.created_at DESC, l.id
            LIMIT 30
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return the list of url ids indexed by urls for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listIdsByUrlsForUser($user_id)
    {
        $sql = <<<SQL
            SELECT id, url FROM links
            WHERE user_id = :user_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);

        $ids_by_urls = [];
        foreach ($statement->fetchAll() as $row) {
            $ids_by_urls[$row['url']] = $row['id'];
        }
        return $ids_by_urls;
    }

    /**
     * Return the list of url ids indexed by urls for the given collection.
     *
     * @param string $collection_id
     *
     * @return array
     */
    public function listIdsByUrlsForCollection($collection_id)
    {
        $sql = <<<SQL
            SELECT l.id, l.url FROM links l, links_to_collections lc
            WHERE lc.link_id = l.id
            AND lc.collection_id = :collection_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);

        $ids_by_urls = [];
        foreach ($statement->fetchAll() as $row) {
            $ids_by_urls[$row['url']] = $row['id'];
        }
        return $ids_by_urls;
    }

    /**
     * Return the list of urls indexed by entry ids for the given collection.
     *
     * @param string $collection_id
     *
     * @return array
     */
    public function listUrlsByEntryIdsForCollection($collection_id)
    {
        $sql = <<<SQL
            SELECT l.id, l.url, l.feed_entry_id
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            ORDER BY lc.created_at DESC, l.id
            LIMIT 200
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);

        $urls_by_entry_ids = [];
        foreach ($statement->fetchAll() as $row) {
            $urls_by_entry_ids[$row['feed_entry_id']] = [
                'url' => $row['url'],
                'id' => $row['id'],
            ];
        }
        return $urls_by_entry_ids;
    }

    /**
     * Return a list of links to fetch (fetched_at is null, or fetched_code is in error).
     *
     * Links in error are not returned if their fetched_count is greater than
     * 25 or if fetched_at is too close (a number of seconds depending on the
     * fetched_count value).
     *
     * @param integer $max_number
     *
     * @return array
     */
    public function listToFetch($max_number)
    {
        $sql = <<<SQL
            SELECT * FROM links
            WHERE fetched_at IS NULL
            OR (
                (fetched_code < 200 OR fetched_code >= 300)
                AND fetched_count <= 25
                AND fetched_at < (?::timestamptz - interval '1 second' * (5 + pow(fetched_count, 4)))
            )
            ORDER BY random()
            LIMIT ?
        SQL;

        $now = \Minz\Time::now();
        $statement = $this->prepare($sql);
        $statement->execute([
            $now->format(\Minz\Model::DATETIME_FORMAT),
            $max_number,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return the number of links to fetch.
     *
     * @return integer
     */
    public function countToFetch()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM links
            WHERE fetched_at IS NULL
            OR (
                (fetched_code < 200 OR fetched_code >= 300)
                AND fetched_count <= 25
                AND fetched_at < (?::timestamptz - interval '1 second' * (5 + pow(fetched_count, 4)))
            )
        SQL;

        $now = \Minz\Time::now();
        $statement = $this->prepare($sql);
        $statement->execute([$now->format(\Minz\Model::DATETIME_FORMAT)]);
        return intval($statement->fetchColumn());
    }

    /**
     * Lock a link
     *
     * @param string $link_id
     *
     * @return boolean True if the lock is successful, false otherwise
     */
    public function lock($link_id)
    {
        $sql = <<<SQL
            UPDATE links
            SET locked_at = :locked_at
            WHERE id = :link_id
            AND locked_at IS NULL
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':locked_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
            ':link_id' => $link_id,
        ]);
        return $statement->rowCount() === 1;
    }

    /**
     * Unlock a link
     *
     * @param string $link_id
     *
     * @return boolean True if the unlock is successful, false otherwise
     */
    public function unlock($link_id)
    {
        $sql = <<<SQL
            UPDATE links
            SET locked_at = null
            WHERE id = :link_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':link_id' => $link_id,
        ]);
        return $statement->rowCount() === 1;
    }
}
