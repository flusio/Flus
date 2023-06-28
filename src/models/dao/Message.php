<?php

namespace flusio\models\dao;

use Minz\Database;

/**
 * Represent a message that comment a link in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Message
{
    /**
     * Return the link messages, orderer by creation date
     *
     * @return self[]
     */
    public static function listByLink(string $link_id): array
    {
        $sql = <<<SQL
             SELECT * FROM messages
             WHERE link_id = ?
             ORDER BY created_at
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$link_id]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
