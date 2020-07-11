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
    use SaveHelper;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Collection::PROPERTIES);
        parent::__construct('collections', 'id', $properties);
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
}
