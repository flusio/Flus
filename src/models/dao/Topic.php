<?php

namespace App\models\dao;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Topic
{
    use MediaQueries;

    /**
     * Returns the list of topics attached to the given collection
     *
     * @return self[]
     */
    public static function listByCollectionId(string $collection_id): array
    {
        $sql = <<<'SQL'
            SELECT t.* FROM topics t, collections_to_topics ct
            WHERE t.id = ct.topic_id AND ct.collection_id = ?;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$collection_id]);
        return self::fromDatabaseRows($statement->fetchAll());
    }
}
