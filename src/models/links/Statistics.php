<?php

namespace App\models\links;

use Minz\Database;

/**
 * Add methods to count the links of the instance.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Statistics
{
    /**
     * Return an estimated number of links.
     *
     * This method have better performance than basic count but is less
     * precise.
     *
     * @see https://wiki.postgresql.org/wiki/Count_estimate
     */
    public static function countEstimated(): int
    {
        $table_name = self::tableName();

        $sql = <<<SQL
            SELECT reltuples AS count
            FROM pg_class
            WHERE relname = ?;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$table_name]);
        return intval($statement->fetchColumn());
    }
}
