<?php

namespace App\models\media;

use Minz\Database;

/**
 * Add an image to a model, stored in the `image_filename` column.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasImage
{
    #[Database\Column]
    public ?string $image_filename = null;

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
            WHERE image_filename LIKE :prefix
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':prefix' => $prefix . '%',
        ]);
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }
}
