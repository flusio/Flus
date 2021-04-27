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
    use BulkHelper;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Collection::PROPERTIES);
        parent::__construct('collections', 'id', $properties);
    }

    /**
     * Returns the list of collections attached to the given link
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
     * Returns the list of collections for the given user id. The number of
     * links of each collection is added. Bookmarks collection is not returned.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listWithNumberLinksForUser($user_id)
    {
        $sql = <<<'SQL'
            SELECT c.*, (
                SELECT COUNT(*) FROM links_to_collections l
                WHERE c.id = l.collection_id
            ) AS number_links
            FROM collections c
            WHERE user_id = ? AND type = 'collection'
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$user_id]);
        return $statement->fetchAll();
    }

    /**
     * Returns the list of feed collections for the given user id and feed URLs.
     * The number of links of each collection is added. The feeds with no
     * associated links are not returned.
     *
     * @param string $user_id
     * @param string[] $feed_urls
     *
     * @return array
     */
    public function listNonEmptyFeedsWithNumberLinks($user_id, $feed_urls)
    {
        if (!$feed_urls) {
            return [];
        }

        $urls_as_question_marks = array_fill(0, count($feed_urls), '?');
        $urls_where_statement = implode(', ', $urls_as_question_marks);

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.id) AS number_links
            FROM collections c, links_to_collections lc

            WHERE c.id = lc.collection_id

            AND c.user_id = ?
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
     * Returns the list of followed collections for the given user id. The
     * number of links of each collection is added.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listFollowedWithNumberLinksForUser($user_id)
    {
        $sql = <<<'SQL'
            SELECT c.*, (
                SELECT COUNT(l.id) FROM links l, links_to_collections lc
                WHERE lc.collection_id = c.id
                AND lc.link_id = l.id
                AND l.is_hidden = false
            ) AS number_links
            FROM collections c, followed_collections fc
            WHERE fc.user_id = ?
            AND fc.collection_id = c.id
            AND c.is_public = true
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$user_id]);
        return $statement->fetchAll();
    }

    /**
     * Return if collection ids exist for the given user.
     *
     * @param string $user_id
     * @param string[] $collection_ids
     *
     * @return boolean True if all the ids exist
     */
    public function existForUser($user_id, $collection_ids)
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
     * Return collections discoverable by the given user.
     *
     * @param string $user_id
     * @param integer $pagination_offset
     * @param integer $pagination_limit
     *
     * @return array
     */
    public function listForDiscovering($user_id, $pagination_offset, $pagination_limit)
    {
        $sql = <<<SQL
            SELECT c.*, COUNT(l.id) AS number_links
            FROM collections c, links l, links_to_collections lc

            WHERE lc.collection_id = c.id
            AND lc.link_id = l.id

            AND l.is_hidden = false
            AND c.is_public = true

            AND c.user_id != :user_id

            AND c.type != 'feed'

            GROUP BY c.id

            ORDER BY c.name
            OFFSET :offset
            LIMIT :limit
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':offset' => $pagination_offset,
            ':limit' => $pagination_limit,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return total count of collections discoverable by the given user.
     *
     * @param string $user_id
     *
     * @return integer
     */
    public function countForDiscovering($user_id)
    {
        $sql = <<<SQL
            SELECT COUNT(DISTINCT c.id)
            FROM collections c, links l, links_to_collections lc

            WHERE lc.collection_id = c.id
            AND lc.link_id = l.id

            AND l.is_hidden = false
            AND c.is_public = true

            AND c.user_id != :user_id

            AND c.type != 'feed'
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the list of ids indexed by names for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listIdsByNames($user_id)
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
    public function listIdsByFeedUrls($user_id)
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
     * List the feeds to be fetched (i.e. not fetched in the last hour).
     *
     * @param \DateTime $before
     * @param integer $limit
     *
     * @return integer
     */
    public function listFeedsToFetch($before, $limit)
    {
        $sql = <<<SQL
            SELECT * FROM collections

            WHERE type = 'feed'
            AND feed_fetched_at <= :before

            ORDER BY feed_fetched_at
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
     * Return the number of feeds that can be fetched (i.e. not fetched in the
     * last hour).
     *
     * @param \DateTime $before
     *
     * @return integer
     */
    public function countFeedsToFetch($before)
    {
        $sql = <<<SQL
            SELECT COUNT(*) FROM collections

            WHERE type = 'feed'
            AND feed_fetched_at <= :before
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':before' => $before->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        return intval($statement->fetchColumn());
    }
}
