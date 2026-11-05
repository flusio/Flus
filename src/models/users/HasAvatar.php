<?php

namespace App\models\users;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasAvatar
{
    #[Database\Column]
    public ?string $avatar_filename;

    /**
     * Return the list of avatar_filename starting with the given string.
     *
     * @return string[]
     */
    public static function listAvatarFilenamesStartingWith(string $prefix): array
    {
        $sql = <<<SQL
            SELECT avatar_filename FROM users
            WHERE avatar_filename LIKE :prefix
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':prefix' => $prefix . '%',
        ]);
        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }
}
