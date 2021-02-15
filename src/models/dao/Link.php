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
     * Return link with given id, with computed number_comments
     *
     * @param string $link_id
     *
     * @return array
     */
    public function findWithNumberComments($link_id)
    {
        $sql = <<<'SQL'
            SELECT l.*, (
                SELECT COUNT(*) FROM messages m
                WHERE m.link_id = l.id
            ) AS number_comments
            FROM links l
            WHERE l.id = ?
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$link_id]);
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
     * @param string $collection_id
     *
     * @return array
     */
    public function listByCollectionIdWithNumberComments($collection_id)
    {
        $sql = <<<'SQL'
            SELECT l.*, (
                SELECT COUNT(*) FROM messages m
                WHERE m.link_id = l.id
            ) AS number_comments
            FROM links l
            WHERE l.id IN (
                SELECT lc.link_id FROM links_to_collections lc
                WHERE lc.collection_id = ?
            )
            ORDER BY l.created_at DESC
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$collection_id]);
        return $statement->fetchAll();
    }

    /**
     * Return public (only) links within the given collection
     *
     * @param string $collection_id
     *
     * @return array
     */
    public function listPublicByCollectionIdWithNumberComments($collection_id)
    {
        $sql = <<<'SQL'
            SELECT l.*, (
                SELECT COUNT(*) FROM messages m
                WHERE m.link_id = l.id
            ) AS number_comments
            FROM links l
            WHERE l.id IN (
                SELECT lc.link_id FROM links_to_collections lc
                WHERE lc.collection_id = ?
            )
            AND l.is_public = '1'
            ORDER BY l.created_at DESC
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$collection_id]);
        return $statement->fetchAll();
    }

    /**
     * Return links listed in bookmarks of the given user, ordered randomly.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listFromBookmarksForNews($user_id)
    {
        $sql = <<<'SQL'
            SELECT l.*, 'bookmarks' AS news_via_type
            FROM links l, collections c, links_to_collections lc

            WHERE lc.link_id = l.id
            AND lc.collection_id = c.id

            AND c.user_id = :user_id
            AND l.user_id = :user_id

            AND c.type = 'bookmarks'

            GROUP BY l.id

            ORDER BY random()
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return public links listed in followed collections of the given user,
     * ordered randomly. Links with a matching url in news_links are not
     * returned.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listFromFollowedCollectionsForNews($user_id)
    {
        $sql = <<<SQL
            SELECT l.*, 'followed' AS news_via_type, c.id AS news_via_collection_id
            FROM links l, collections c, links_to_collections lc, followed_collections fc

            WHERE fc.user_id = :user_id
            AND fc.collection_id = lc.collection_id

            AND lc.link_id = l.id
            AND lc.collection_id = c.id

            AND l.is_public = true
            AND c.is_public = true

            AND l.url NOT IN (
                SELECT nl.url FROM news_links nl
                WHERE nl.user_id = :user_id
            )

            GROUP BY l.id, c.id

            ORDER BY random()
            LIMIT 500
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return public links based on interests of the given user, ordered
     * randomly. Links with a matching url in news_links are not returned.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listFromTopicsForNews($user_id)
    {
        $sql = <<<SQL
            SELECT l.*, 'topics' AS news_via_type, ct.collection_id AS news_via_collection_id
            FROM links l, links_to_collections lc, collections_to_topics ct

            WHERE ct.topic_id IN (
                SELECT ut.topic_id FROM users_to_topics ut
                WHERE ut.user_id = :user_id
            )

            AND ct.collection_id = lc.collection_id
            AND lc.link_id = l.id

            AND l.is_public = true
            AND l.user_id != :user_id
            AND l.url NOT IN (
                SELECT nl.url FROM news_links nl
                WHERE nl.user_id = :user_id
            )

            GROUP BY l.id, ct.collection_id

            ORDER BY random()
            LIMIT 500
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return links with oldest fetched_at date.
     *
     * @param integer $number
     *
     * @return array
     */
    public function listByOldestFetching($number)
    {
        $sql = <<<SQL
             SELECT * FROM links
             ORDER BY fetched_at
             LIMIT ?
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$number]);
        return $statement->fetchAll();
    }

    /**
     * Return the list of url ids indexed by urls for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listIdsByUrls($user_id)
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
     * Return a list of links to fetch (fetched_at = null) of the given user
     *
     * @param string $user_id
     * @param integer $number
     *
     * @return array
     */
    public function listToFetch($user_id, $number)
    {
        $sql = <<<SQL
             SELECT * FROM links
             WHERE fetched_at IS NULL
             AND user_id = ?
             ORDER BY created_at DESC
             LIMIT ?
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$user_id, $number]);
        return $statement->fetchAll();
    }

    /**
     * Return the number of links to fetch (fetched_at = null) of the given user
     *
     * @param string $user_id
     *
     * @return integer
     */
    public function countToFetch($user_id)
    {
        $sql = <<<SQL
             SELECT COUNT(*) FROM links
             WHERE fetched_at IS NULL
             AND user_id = ?
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$user_id]);
        return intval($statement->fetchColumn());
    }
}
