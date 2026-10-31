<?php

namespace App\models\collections;

use Minz\Database;

/**
 * Add methods to delete the collections that don't have to be kept anymore.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Prunable
{
    /**
     * Delete not followed collections older than the given date.
     */
    public static function deleteUnfollowedFeedsOlderThan(\DateTimeImmutable $date): bool
    {
        $sql = <<<SQL
            DELETE FROM collections

            USING collections AS c

            LEFT JOIN followed_collections AS fc
            ON c.id = fc.collection_id

            WHERE collections.id = c.id
            AND c.type = 'feed'
            AND c.created_at < :date
            AND fc.collection_id IS NULL;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);

        return $statement->execute([
            ':date' => $date->format(Database\Column::DATETIME_FORMAT),
        ]);
    }
}
