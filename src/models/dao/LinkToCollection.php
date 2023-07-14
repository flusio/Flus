<?php

namespace flusio\models\dao;

use Minz\Database;

/**
 * Connect links to collections
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait LinkToCollection
{
    use BulkQueries;

    /**
     * Attach the collections to the given links.
     *
     * @param string[] $link_ids
     * @param string[] $collection_ids
     */
    public static function attach(
        array $link_ids,
        array $collection_ids,
        ?\DateTimeImmutable $created_at = null,
    ): bool {
        if (!$link_ids || !$collection_ids) {
            // nothing to insert
            return true;
        }

        if (!$created_at) {
            $created_at = \Minz\Time::now();
        }

        $values_as_question_marks = [];
        $values = [];
        foreach ($link_ids as $link_id) {
            foreach ($collection_ids as $collection_id) {
                $values_as_question_marks[] = '(?, ?, ?)';
                $values = array_merge($values, [
                    $created_at->format(Database\Column::DATETIME_FORMAT),
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

        $database = Database::get();
        $statement = $database->prepare($sql);
        $result = $statement->execute($values);

        return $database->lastInsertId();
    }

    /**
     * Detach the collections from the given links.
     *
     * @param string[] $link_ids
     * @param string[] $collection_ids
     */
    public static function detach(array $link_ids, array $collection_ids): bool
    {
        if (!$link_ids || !$collection_ids) {
            // nothing to delete
            return true;
        }

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

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($values);
    }

    /**
     * Detach the collections from the given links (only of 'collections' type).
     *
     * @param string[] $link_ids
     * @param string[] $collection_ids
     */
    public static function detachCollections(array $link_ids, array $collection_ids): bool
    {
        if (!$link_ids || !$collection_ids) {
            // nothing to delete
            return true;
        }

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
            AND c.type = 'collection'
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($values);
    }
}
