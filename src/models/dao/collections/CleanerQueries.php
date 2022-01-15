<?php

namespace flusio\models\dao\collections;

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
     *
     * @param string $user_id
     * @param \DateTime $date
     *
     * @return boolean True on success
     */
    public function deleteUnfollowedOlderThan($user_id, $date)
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

        $statement = $this->prepare($sql);
        return $statement->execute([
            ':user_id' => $user_id,
            ':date' => $date->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }
}
