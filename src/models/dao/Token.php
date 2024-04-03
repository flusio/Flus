<?php

namespace App\models\dao;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Token
{
    /**
     * Delete tokens that have expired.
     */
    public static function deleteExpired(): bool
    {
        $sql = <<<SQL
            DELETE FROM tokens
            WHERE expired_at <= ?
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute([
            \Minz\Time::now()->format(Database\Column::DATETIME_FORMAT),
        ]);
    }
}
