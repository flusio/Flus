<?php

namespace flusio\models\dao;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionsToTopics extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = ['id', 'collection_id', 'topic_id'];
        parent::__construct('collections_to_topics', 'id', $properties);
    }

    /**
     * Attach the topics to the given collection.
     *
     * @param string $collection_id
     * @param string[] $topic_ids
     *
     * @return boolean True on success
     */
    public function attach($collection_id, $topic_ids)
    {
        $values_as_question_marks = [];
        $values = [];
        foreach ($topic_ids as $topic_id) {
            $values_as_question_marks[] = '(?, ?)';
            $values = array_merge($values, [$collection_id, $topic_id]);
        }
        $values_placeholder = implode(", ", $values_as_question_marks);

        $sql = <<<SQL
            INSERT INTO collections_to_topics (collection_id, topic_id)
            VALUES {$values_placeholder};
        SQL;

        $statement = $this->prepare($sql);
        $result = $statement->execute($values);
        return $this->lastInsertId();
    }

    /**
     * Detach the topics from the given collection.
     *
     * @param string $collection_id
     * @param string[] $topic_ids
     *
     * @return boolean True on success
     */
    public function detach($collection_id, $topic_ids)
    {
        $values_as_question_marks = [];
        $values = [];
        foreach ($topic_ids as $topic_id) {
            $values_as_question_marks[] = '(collection_id = ? AND topic_id = ?)';
            $values = array_merge($values, [$collection_id, $topic_id]);
        }
        $values_placeholder = implode(' OR ', $values_as_question_marks);

        $sql = <<<SQL
            DELETE FROM collections_to_topics
            WHERE {$values_placeholder};
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute($values);
    }

    /**
     * Attach the topics to the given collection and remove old ones if any.
     *
     * @param string $collection_id
     * @param string[] $topic_ids
     *
     * @return boolean True on success
     */
    public function set($collection_id, $topic_ids)
    {
        $previous_attachments = $this->listBy(['collection_id' => $collection_id]);
        $previous_topic_ids = array_column($previous_attachments, 'topic_id');
        $ids_to_attach = array_diff($topic_ids, $previous_topic_ids);
        $ids_to_detach = array_diff($previous_topic_ids, $topic_ids);

        $this->beginTransaction();

        if ($ids_to_attach) {
            $this->attach($collection_id, $ids_to_attach);
        }

        if ($ids_to_detach) {
            $this->detach($collection_id, $ids_to_detach);
        }

        return $this->commit();
    }
}
