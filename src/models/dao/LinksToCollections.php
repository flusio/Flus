<?php

namespace flusio\models\dao;

/**
 * Connect links to collections
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksToCollections extends \Minz\DatabaseModel
{
    use BulkHelper;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = ['id', 'created_at', 'link_id', 'collection_id'];
        parent::__construct('links_to_collections', 'id', $properties);
    }

    /**
     * Attach the collections to the given link.
     *
     * @param string $link_id
     * @param string[] $collection_ids
     *
     * @return boolean True on success
     */
    public function attach($link_id, $collection_ids)
    {
        $now = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);
        $values_as_question_marks = [];
        $values = [];
        foreach ($collection_ids as $collection_id) {
            $values_as_question_marks[] = '(?, ?, ?)';
            $values = array_merge($values, [$now, $link_id, $collection_id]);
        }
        $values_placeholder = implode(", ", $values_as_question_marks);

        $sql = <<<SQL
            INSERT INTO links_to_collections (created_at, link_id, collection_id)
            VALUES {$values_placeholder};
        SQL;

        $statement = $this->prepare($sql);
        $result = $statement->execute($values);
        return $this->lastInsertId();
    }

    /**
     * Detach the collections from the given link.
     *
     * @param string $link_id
     * @param string[] $collection_ids
     *
     * @return boolean True on success
     */
    public function detach($link_id, $collection_ids)
    {
        $values_as_question_marks = [];
        $values = [];
        foreach ($collection_ids as $collection_id) {
            $values_as_question_marks[] = '(link_id = ? AND collection_id = ?)';
            $values = array_merge($values, [$link_id, $collection_id]);
        }
        $values_placeholder = implode(' OR ', $values_as_question_marks);

        $sql = <<<SQL
            DELETE FROM links_to_collections
            WHERE {$values_placeholder};
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute($values);
    }

    /**
     * Attach the collections to the given link and remove old ones if any.
     *
     * @param string $link_id
     * @param string[] $collection_ids
     *
     * @return boolean True on success
     */
    public function set($link_id, $collection_ids)
    {
        $previous_attachments = $this->listBy(['link_id' => $link_id]);
        $previous_collection_ids = array_column($previous_attachments, 'collection_id');
        $ids_to_attach = array_diff($collection_ids, $previous_collection_ids);
        $ids_to_detach = array_diff($previous_collection_ids, $collection_ids);

        $this->beginTransaction();

        if ($ids_to_attach) {
            $this->attach($link_id, $ids_to_attach);
        }

        if ($ids_to_detach) {
            $this->detach($link_id, $ids_to_detach);
        }

        return $this->commit();
    }
}
