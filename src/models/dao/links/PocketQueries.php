<?php

namespace flusio\models\dao\links;

/**
 * Add methods providing SQL queries specific to the Pocket importation system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait PocketQueries
{
    /**
     * Return the list of url ids indexed by urls for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listUrlsToIdsByUserId($user_id)
    {
        $sql = <<<SQL
            SELECT url, id FROM links
            WHERE user_id = :user_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
