<?php

namespace App\models\dao\collections;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the OPML importation system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait OpmlImportatorQueries
{
    /**
     * Return the list of ids indexed by feed urls.
     *
     * @return array<string, string>
     */
    public static function listFeedUrlsToIds(): array
    {
        $sql = <<<SQL
            SELECT feed_url, id FROM collections
            WHERE type = 'feed'
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }
}
