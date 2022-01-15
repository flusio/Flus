<?php

namespace flusio\models\dao;

/**
 * Represent a collection of flusio in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collection extends \Minz\DatabaseModel
{
    use BulkQueries;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Collection::PROPERTIES);
        parent::__construct('collections', 'id', $properties);
    }

    /**
     * Return the number of collections (type "collection").
     *
     * @return integer
     */
    public function countCollections()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'collection'
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of feeds (type "feed").
     *
     * @return integer
     */
    public function countFeeds()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'feed'
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of feeds (type "feed") by hours.
     *
     * @return integer[]
     */
    public function countFeedsByHours()
    {
        $sql = <<<'SQL'
            SELECT TO_CHAR(feed_fetched_at, 'HH24') AS hour, COUNT(*) as count
            FROM collections
            WHERE type = 'feed'
            GROUP BY hour
        SQL;

        $statement = $this->query($sql);
        $result = $statement->fetchAll();
        $count_by_hours = [];
        foreach ($result as $row) {
            $count_by_hours[$row['hour']] = intval($row['count']);
        }
        ksort($count_by_hours);
        return $count_by_hours;
    }

    /**
     * Return the number of collections (type "collection").
     *
     * @return integer
     */
    public function countCollectionsPublic()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'collection'
            AND is_public = true
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the list of collections attached to the given link.
     *
     * @param string $link_id
     *
     * @return array
     */
    public function listByLinkId($link_id)
    {
        $sql = <<<'SQL'
            SELECT * FROM collections
            WHERE id IN (
                SELECT collection_id FROM links_to_collections
                WHERE link_id = ?
            )
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$link_id]);
        return $statement->fetchAll();
    }

    /**
     * Return the list of public collections attached to the given topic.
     *
     * @param string $topic_id
     * @param integer $pagination_offset
     * @param integer $pagination_limit
     *
     * @return array
     */
    public function listPublicByTopicIdWithNumberLinks($topic_id, $pagination_offset, $pagination_limit)
    {
        $sql = <<<'SQL'
            SELECT c.*, COUNT(lc.*) AS number_links
            FROM collections c, collections_to_topics ct, links_to_collections lc, links l

            WHERE c.id = ct.collection_id
            AND c.id = lc.collection_id
            AND l.id = lc.link_id

            AND c.is_public = true
            AND l.is_hidden = false
            AND ct.topic_id = :topic_id

            GROUP BY c.id

            ORDER BY c.name
            OFFSET :offset
            LIMIT :limit
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':topic_id' => $topic_id,
            ':offset' => $pagination_offset,
            ':limit' => $pagination_limit,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return the list of public collections of the given user.
     *
     * @param string $user_id
     * @param boolean $count_hidden_links
     *     Indicate if number_links should include hidden links
     *
     * @return array
     */
    public function listPublicByUserIdWithNumberLinks($user_id, $count_hidden_links)
    {
        $is_hidden_placeholder = '';
        if (!$count_hidden_links) {
            $is_hidden_placeholder = 'AND l.is_hidden = false';
        }

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.*) AS number_links
            FROM collections c, links_to_collections lc, links l

            WHERE c.id = lc.collection_id
            AND l.id = lc.link_id

            AND c.is_public = true
            AND c.type = 'collection'
            {$is_hidden_placeholder}

            AND c.user_id = :user_id

            GROUP BY c.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Count the public collections attached to the given topic.
     *
     * @param string $topic_id
     *
     * @return integer
     */
    public function countPublicByTopicId($topic_id)
    {
        $sql = <<<'SQL'
            SELECT COUNT(DISTINCT c.id)
            FROM collections c, collections_to_topics ct, links_to_collections lc, links l

            WHERE c.id = ct.collection_id
            AND c.id = lc.collection_id
            AND l.id = lc.link_id

            AND c.is_public = true
            AND l.is_hidden = false
            AND ct.topic_id = :topic_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':topic_id' => $topic_id,
        ]);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the collections of the given user and in the given group.
     *
     * If group id is null, it returns the collections in no groups.
     *
     * @param string $user_id
     * @param string $group_id
     *
     * @return array
     */
    public function listByUserIdAndGroupIdWithNumberLinks($user_id, $group_id)
    {
        $values = [':user_id' => $user_id];

        if ($group_id) {
            $group_placeholder = 'AND group_id = :group_id';
            $values[':group_id'] = $group_id;
        } else {
            $group_placeholder = 'AND group_id IS NULL';
        }

        $sql = <<<SQL
            SELECT c.*, (
                SELECT COUNT(*) FROM links_to_collections l
                WHERE c.id = l.collection_id
            ) AS number_links
            FROM collections c
            WHERE user_id = :user_id
            AND type = 'collection'
            {$group_placeholder}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return all the collections of the given user with their number of links.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listForLinksPage($user_id)
    {
        $values = [':user_id' => $user_id];

        $sql = <<<SQL
            SELECT c.*, (
                SELECT COUNT(lc.*) FROM links_to_collections lc
                WHERE lc.collection_id = c.id
            ) AS number_links
            FROM collections c
            WHERE user_id = :user_id
            AND type = 'collection'
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return all the followed collections of the given user with their number
     * of visible links.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listForFeedsPage($user_id)
    {
        $values = [':user_id' => $user_id];

        $sql = <<<SQL
            SELECT c.*, (
                SELECT COUNT(l.id) FROM links l, links_to_collections lc
                WHERE lc.collection_id = c.id
                AND lc.link_id = l.id
                AND l.is_hidden = false
            ) AS number_links, fc.group_id
            FROM collections c, followed_collections fc
            WHERE fc.user_id = :user_id
            AND fc.collection_id = c.id
            AND c.is_public = true
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return the list of feeds of the given user and with the given feed URLs.
     *
     * @param string $user_id
     * @param string[] $feed_urls
     *
     * @return array
     */
    public function listByUserIdAndFeedUrlsWithNumberLinks($user_id, $feed_urls)
    {
        if (!$feed_urls) {
            return [];
        }

        $urls_as_question_marks = array_fill(0, count($feed_urls), '?');
        $urls_where_statement = implode(', ', $urls_as_question_marks);

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.id) AS number_links
            FROM collections c

            LEFT JOIN links_to_collections lc
            ON c.id = lc.collection_id

            WHERE c.user_id = ?
            AND c.type = 'feed'
            AND c.feed_url IN ({$urls_where_statement})
            AND c.is_public = true

            GROUP BY c.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute(array_merge([$user_id], $feed_urls));
        return $statement->fetchAll();
    }

    /**
     * Return the followed collections of the given user and in the given group.
     *
     * If group id is null, it returns the collections in no groups.
     *
     * @param string $user_id
     * @param string $group_id
     *
     * @return array
     */
    public function listFollowedByUserIdAndGroupIdWithNumberLinks($user_id, $group_id)
    {
        $values = [':user_id' => $user_id];

        if ($group_id) {
            $group_placeholder = 'AND fc.group_id = :group_id';
            $values[':group_id'] = $group_id;
        } else {
            $group_placeholder = 'AND fc.group_id IS NULL';
        }

        $sql = <<<SQL
            SELECT c.*, (
                SELECT COUNT(l.id) FROM links l, links_to_collections lc
                WHERE lc.collection_id = c.id
                AND lc.link_id = l.id
                AND l.is_hidden = false
            ) AS number_links
            FROM collections c, followed_collections fc
            WHERE fc.user_id = :user_id
            AND fc.collection_id = c.id
            AND c.is_public = true
            {$group_placeholder}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return whether the given user owns the given collections or not.
     *
     * @param string $user_id
     * @param string[] $collection_ids
     *
     * @return boolean True if all the ids exist
     */
    public function doesUserOwnCollections($user_id, $collection_ids)
    {
        if (empty($collection_ids)) {
            return true;
        }

        $matching_rows = $this->listBy([
            'id' => $collection_ids,
            'user_id' => $user_id,
        ]);
        return count($matching_rows) === count($collection_ids);
    }

    /**
     * Return the list of ids indexed by names for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listNamesToIdsByUserId($user_id)
    {
        $sql = <<<SQL
            SELECT id, name FROM collections
            WHERE user_id = :user_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);

        $ids_by_names = [];
        foreach ($statement->fetchAll() as $row) {
            $ids_by_names[$row['name']] = $row['id'];
        }
        return $ids_by_names;
    }

    /**
     * Return the list of ids indexed by feed urls for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listFeedUrlsToIdsByUserId($user_id)
    {
        $sql = <<<SQL
            SELECT id, feed_url FROM collections
            WHERE user_id = :user_id
            AND type = 'feed'
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);

        $ids_by_feed_urls = [];
        foreach ($statement->fetchAll() as $row) {
            $ids_by_feed_urls[$row['feed_url']] = $row['id'];
        }
        return $ids_by_feed_urls;
    }

    /**
     * List (randomly) active feeds to be fetched.
     *
     * An active feed is a feed followed by at least one active user (i.e.
     * account has been validated)
     *
     * @param \DateTime $before
     * @param integer $limit
     *
     * @return array
     */
    public function listActiveFeedsToFetch($before, $limit)
    {
        $sql = <<<SQL
            SELECT c.*
            FROM collections c, followed_collections fc, users u

            WHERE c.type = 'feed'
            AND (
                c.feed_fetched_at <= :before
                OR c.feed_fetched_at IS NULL
            )

            AND c.id = fc.collection_id
            AND u.id = fc.user_id

            -- We prioritize feeds followed by active users. We ignore for now
            -- expired subscriptions, but it could be checked too.
            AND u.validated_at IS NOT NULL

            ORDER BY random()
            LIMIT :limit
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':before' => $before->format(\Minz\Model::DATETIME_FORMAT),
            ':limit' => $limit,
        ]);
        return $statement->fetchAll();
    }

    /**
     * List feeds that haven't been fetched for the longest time.
     *
     * @param \DateTime $before
     * @param integer $limit
     *
     * @return array
     */
    public function listOldestFeedsToFetch($before, $limit)
    {
        $sql = <<<SQL
            SELECT c.*
            FROM collections c

            WHERE c.type = 'feed'
            AND (
                c.feed_fetched_at <= :before
                OR c.feed_fetched_at IS NULL
            )

            ORDER BY feed_fetched_at NULLS FIRST
            LIMIT :limit
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':before' => $before->format(\Minz\Model::DATETIME_FORMAT),
            ':limit' => $limit,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Delete not followed collections older than the given date for the given
     * user.
     *
     * @param string $user_id
     * @param \DateTime $date
     *
     * @return boolean True on success
     */
    public function deleteUnfollowedOlderThan($user_id, $date)
    {
        $sql = <<<SQL
            DELETE FROM collections

            USING collections AS c

            LEFT JOIN followed_collections AS fc
            ON c.id = fc.collection_id

            WHERE collections.id = c.id
            AND c.user_id = :user_id
            AND c.created_at < :date
            AND fc.collection_id IS NULL;
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute([
            ':user_id' => $user_id,
            ':date' => $date->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }

    /**
     * Lock a collection
     *
     * @param string $collection_id
     *
     * @return boolean True if the lock is successful, false otherwise
     */
    public function lock($collection_id)
    {
        $sql = <<<SQL
            UPDATE collections
            SET locked_at = :locked_at
            WHERE id = :collection_id
            AND (locked_at IS NULL OR locked_at <= :lock_timeout)
        SQL;

        $now = \Minz\Time::now();
        $lock_timeout = \Minz\Time::ago(1, 'hour');
        $statement = $this->prepare($sql);
        $statement->execute([
            ':locked_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            ':collection_id' => $collection_id,
            ':lock_timeout' => $lock_timeout->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        return $statement->rowCount() === 1;
    }

    /**
     * Unlock a collection
     *
     * @param string $collection_id
     *
     * @return boolean True if the unlock is successful, false otherwise
     */
    public function unlock($collection_id)
    {
        $sql = <<<SQL
            UPDATE collections
            SET locked_at = null
            WHERE id = :collection_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);
        return $statement->rowCount() === 1;
    }
}
