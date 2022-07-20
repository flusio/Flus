<?php

namespace flusio\models\dao\collections;

/**
 * Add methods providing SQL queries specific to the Pocket importation system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait PocketQueries
{
    /**
     * Return the list of ids indexed by names for the given user.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listNamesToIdsByUserId($user_id)
    {
        $sql = <<<SQL
            SELECT name, id FROM collections
            WHERE user_id = :user_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
