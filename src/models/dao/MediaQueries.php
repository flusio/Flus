<?php

namespace flusio\models\dao;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the media (i.e. image_filename).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait MediaQueries
{
    /**
     * Returns the list of image_filename starting with the given string.
     *
     * @return string[]
     */
    public static function listImageFilenamesStartingWith(string $prefix): array
    {
        $table_name = self::tableName();

        $sql = <<<SQL
            SELECT image_filename FROM {$table_name}
            WHERE image_filename LIKE ?
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            $prefix . '%',
        ]);
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }
}
