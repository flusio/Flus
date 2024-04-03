<?php

namespace App\models\dao\links;

use Minz\Database;

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
     * @return array<string, string>
     */
    public static function listUrlsToIdsByUserId(string $user_id): array
    {
        $sql = <<<SQL
            SELECT url, id FROM links
            WHERE user_id = :user_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
