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
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = ['id', 'link_id', 'collection_id'];
        parent::__construct('links_to_collections', 'id', $properties);
    }

    /**
     * Attach the collections to the given link.
     *
     * @param string $link_id
     * @param string[] $collection_ids
     *
     * @throws \Minz\Errors\DatabaseModelError
     *
     * @return boolean True on success
     */
    public function attachCollectionsToLink($link_id, $collection_ids)
    {
        $values_as_question_marks = [];
        $values = [];
        foreach ($collection_ids as $collection_id) {
            $values_as_question_marks[] = '(?, ?)';
            $values = array_merge($values, [$link_id, $collection_id]);
        }
        $values_placeholder = implode(", ", $values_as_question_marks);

        $sql = <<<SQL
            INSERT INTO links_to_collections (link_id, collection_id)
            VALUES {$values_placeholder};
        SQL;

        $statement = $this->prepare($sql);
        $result = $statement->execute($values);

        if ($result) {
            return $this->lastInsertId();
        } else {
            throw self::sqlStatementError($statement);
        }
    }
}
