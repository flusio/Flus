<?php

namespace flusio\models\dao\links;

/**
 * Add methods providing SQL queries specific to the Cleaner.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait CleanerQueries
{
    /**
     * Delete links that are attached to no collections older than the given
     * date for the given user.
     *
     * @param string $user_id
     * @param \DateTime $date
     *
     * @return boolean True on success
     */
    public function deleteNotStoredOlderThan($user_id, $date)
    {
        $sql = <<<SQL
            DELETE FROM links

            USING links AS l

            LEFT JOIN links_to_collections AS lc
            ON l.id = lc.link_id

            WHERE links.id = l.id
            AND l.user_id = :user_id
            AND l.created_at < :date
            AND lc.link_id IS NULL;
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute([
            ':user_id' => $user_id,
            ':date' => $date->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }
}
