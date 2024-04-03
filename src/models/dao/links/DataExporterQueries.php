<?php

namespace App\models\dao\links;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the DataExporter.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait DataExporterQueries
{
    /**
     * Return links of the given user which have at least one comment.
     *
     * @return self[]
     */
    public static function listByUserIdWithComments(string $user_id): array
    {
        $sql = <<<SQL
            SELECT l.*
            FROM links l, messages m

            WHERE l.id = m.link_id
            AND l.user_id = :user_id

            ORDER BY l.created_at DESC, l.id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
