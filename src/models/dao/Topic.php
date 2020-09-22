<?php

namespace flusio\models\dao;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Topic extends \Minz\DatabaseModel
{
    use SaveHelper;

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

    /**
     * Returns the list of topics attached to the given user
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listByUserId($user_id)
    {
        $sql = <<<'SQL'
            SELECT t.* FROM topics t, users_to_topics ut
            WHERE t.id = ut.topic_id AND ut.user_id = ?;
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$user_id]);
        return $statement->fetchAll();
    }
}
