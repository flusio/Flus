<?php

namespace App\models\dao\collections;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the Cleaner.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait CleanerQueries
{
    /**
     * Delete not followed collections older than the given date for the given
     * user.
     */
    public static function deleteUnfollowedOlderThan(string $user_id, \DateTimeImmutable $date): bool
    {
        $sql = <<<SQL
            DELETE FROM collections

            USING collections AS c

            LEFT JOIN followed_collections AS fc
            ON c.id = fc.collection_id

            WHERE collections.id = c.id
            AND c.user_id = :user_id
            AND c.created_at < :date
            AND fc.collection_id IS NULL;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);

        return $statement->execute([
            ':user_id' => $user_id,
            ':date' => $date->format(Database\Column::DATETIME_FORMAT),
        ]);
    }

    /**
     * Set all the feed_last_hash to empty string.
     */
    public static function resetHashes(): int
    {
        $sql = <<<SQL
            UPDATE collections
            SET feed_last_hash = ''
            WHERE type = 'feed'
        SQL;

        $database = Database::get();
        return $database->exec($sql);
    }
}
