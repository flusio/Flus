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
    use SaveHelper;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Link::PROPERTIES);
        parent::__construct('links', 'id', $properties);
    }

    /**
     * Return links within the given collection
     *
     * @param string $collection_id
     *
     * @return array
     */
    public function listByCollectionId($collection_id)
    {
        $sql = <<<'SQL'
            SELECT * FROM links WHERE id IN (
                SELECT link_id FROM links_to_collections
                WHERE collection_id = ?
            )
            ORDER BY created_at DESC
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
    public function listPublicByCollectionId($collection_id)
    {
        $sql = <<<'SQL'
            SELECT * FROM links WHERE id IN (
                SELECT link_id FROM links_to_collections
                WHERE collection_id = ? AND is_public = '1'
            )
            ORDER BY created_at DESC
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$collection_id]);
        return $statement->fetchAll();
    }

    /**
     * Return public links with same URL, listed in followed collections of the
     * given user.
     *
     * @param string $user_id
     * @param string $url
     *
     * @return array
     */
    public function listFromFollowedByUrl($user_id, $url)
    {
        $sql = <<<SQL
             SELECT l.* FROM links l, collections c, links_to_collections lc, followed_collections fc

             WHERE fc.user_id = :user_id
             AND fc.collection_id = lc.collection_id

             AND lc.link_id = l.id
             AND lc.collection_id = c.id

             AND l.is_public = true
             AND c.is_public = true

             AND l.url = :url

             GROUP BY l.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':url' => $url,
        ]);
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
            SELECT l.* FROM links l, collections c, links_to_collections lc

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
             SELECT l.* FROM links l, collections c, links_to_collections lc, followed_collections fc

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

             GROUP BY l.id

             ORDER BY random()
             LIMIT 500
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }
}
