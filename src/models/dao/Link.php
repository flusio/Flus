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
}
