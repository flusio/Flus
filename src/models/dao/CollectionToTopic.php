<?php

namespace flusio\models\dao;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait CollectionToTopic
{
    /**
     * Attach the topics to the given collection.
     *
     * @param string[] $topic_ids
     */
    public static function attach(string $collection_id, array $topic_ids): bool
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

        $database = Database::get();
        $statement = $database->prepare($sql);
        $result = $statement->execute($values);

        return $database->lastInsertId();
    }

    /**
     * Detach the topics from the given collection.
     *
     * @param string[] $topic_ids
     */
    public static function detach(string $collection_id, array $topic_ids): bool
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

        $database = database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($values);
    }

    /**
     * Attach the topics to the given collection and remove old ones if any.
     *
     * @param string[] $topic_ids
     */
    public static function set(string $collection_id, array $topic_ids): bool
    {
        $previous_attachments = self::listBy(['collection_id' => $collection_id]);
        $previous_topic_ids = array_column($previous_attachments, 'topic_id');
        $ids_to_attach = array_diff($topic_ids, $previous_topic_ids);
        $ids_to_detach = array_diff($previous_topic_ids, $topic_ids);

        $database = Database::get();
        $database->beginTransaction();

        if ($ids_to_attach) {
            self::attach($collection_id, $ids_to_attach);
        }

        if ($ids_to_detach) {
            self::detach($collection_id, $ids_to_detach);
        }

        return $database->commit();
    }
}
