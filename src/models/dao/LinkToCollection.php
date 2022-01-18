<?php

namespace flusio\models\dao;

/**
 * Connect links to collections
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkToCollection extends \Minz\DatabaseModel
{
    use BulkQueries;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\LinkToCollection::PROPERTIES);
        parent::__construct('links_to_collections', 'id', $properties);
    }

    /**
     * Attach the collections to the given links.
     *
     * @param string[] $link_ids
     * @param string[] $collection_ids
     * @param \DateTime $created_at Value to set as created_at, "now" by default
     *
     * @return boolean True on success
     */
    public function attach($link_ids, $collection_ids, $created_at = null)
    {
        if (!$created_at) {
            $created_at = \Minz\Time::now();
        }
        $values_as_question_marks = [];
        $values = [];
        foreach ($link_ids as $link_id) {
            foreach ($collection_ids as $collection_id) {
                $values_as_question_marks[] = '(?, ?, ?)';
                $values = array_merge($values, [
                    $created_at->format(\Minz\Model::DATETIME_FORMAT),
                    $link_id,
                    $collection_id,
                ]);
            }
        }
        $values_placeholder = implode(", ", $values_as_question_marks);

        $sql = <<<SQL
            INSERT INTO links_to_collections (created_at, link_id, collection_id)
            VALUES {$values_placeholder}
            ON CONFLICT DO NOTHING;
        SQL;

        $statement = $this->prepare($sql);
        $result = $statement->execute($values);
        return $this->lastInsertId();
    }

    /**
     * Detach the collections from the given links.
     *
     * @param string[] $link_ids
     * @param string[] $collection_ids
     *
     * @return boolean True on success
     */
    public function detach($link_ids, $collection_ids)
    {
        $values_as_question_marks = [];
        $values = [];
        foreach ($link_ids as $link_id) {
            foreach ($collection_ids as $collection_id) {
                $values_as_question_marks[] = '(link_id = ? AND collection_id = ?)';
                $values = array_merge($values, [$link_id, $collection_id]);
            }
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
     * Detach the collections from the given links (only collections of type
     * 'collection' and 'bookmarks').
     *
     * @param string[] $link_ids
     * @param string[] $collection_ids
     *
     * @return boolean True on success
     */
    public function detachCollections($link_ids, $collection_ids)
    {
        $values_as_question_marks = [];
        $values = [];
        foreach ($link_ids as $link_id) {
            foreach ($collection_ids as $collection_id) {
                $values_as_question_marks[] = '(link_id = ? AND collection_id = ?)';
                $values = array_merge($values, [$link_id, $collection_id]);
            }
        }
        $values_placeholder = implode(' OR ', $values_as_question_marks);

        $sql = <<<SQL
            DELETE FROM links_to_collections lc
            USING collections c
            WHERE ({$values_placeholder})
            AND c.id = lc.collection_id
            AND (c.type = 'collection' OR c.type = 'bookmarks')
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute($values);
    }
}
