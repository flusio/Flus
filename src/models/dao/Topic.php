<?php

namespace flusio\models\dao;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Topic extends \Minz\DatabaseModel
{
    use MediaQueries;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Topic::PROPERTIES);
        parent::__construct('topics', 'id', $properties);
    }

    /**
     * Returns the list of topics attached to the given collection
     *
     * @param string $collection_id
     *
     * @return array
     */
    public function listByCollectionId($collection_id)
    {
        $sql = <<<'SQL'
            SELECT t.* FROM topics t, collections_to_topics ct
            WHERE t.id = ct.topic_id AND ct.collection_id = ?;
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$collection_id]);
        return $statement->fetchAll();
    }
}
