<?php

namespace App\models\links;

use App\models\User;
use App\utils\Pagination;
use Minz\Database;

/**
 * Add methods to list the links that a user marked as read or to read later.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Readable
{
    /**
     * Return the list of read later links of the given user.
     *
     * @return self[]
     */
    public static function listReadLater(User $user, ?Pagination $pagination): array
    {
        $parameters = [
            ':user_id' => $user->id,
        ];

        $pagination_clause = '';
        if ($pagination) {
            $pagination_clause = 'LIMIT :limit OFFSET :offset';
            $parameters[':limit'] = $pagination->numberPerPage();
            $parameters[':offset'] = $pagination->currentOffset();
        }

        $sql = <<<SQL
            SELECT l.*, us.read_later_at AS published_at
            FROM links l
            INNER JOIN url_statuses us ON us.user_id = :user_id AND l.url_hash = us.url_hash

            WHERE l.user_id = :user_id
            AND us.read_later_at IS NOT NULL

            ORDER BY published_at DESC, l.id

            {$pagination_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the count of read later links of the given user.
     */
    public static function countReadLater(User $user): int
    {
        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l
            INNER JOIN url_statuses us ON us.user_id = :user_id AND l.url_hash = us.url_hash

            WHERE l.user_id = :user_id
            AND us.read_later_at IS NOT NULL
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
        ]);

        return intval($statement->fetchColumn());
    }

    /**
     * Return the list of read links of the given user.
     *
     * @return self[]
     */
    public static function listRead(User $user, ?Pagination $pagination): array
    {
        $parameters = [
            ':user_id' => $user->id,
        ];

        $pagination_clause = '';
        if ($pagination) {
            $pagination_clause = 'LIMIT :limit OFFSET :offset';
            $parameters[':limit'] = $pagination->numberPerPage();
            $parameters[':offset'] = $pagination->currentOffset();
        }

        $sql = <<<SQL
            SELECT l.*, us.read_at AS published_at
            FROM links l
            INNER JOIN url_statuses us ON us.user_id = :user_id AND l.url_hash = us.url_hash

            WHERE l.user_id = :user_id
            AND us.read_at IS NOT NULL

            ORDER BY published_at DESC, l.id

            {$pagination_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the count of read links of the given user.
     */
    public static function countRead(User $user): int
    {
        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l
            INNER JOIN url_statuses us ON us.user_id = :user_id AND l.url_hash = us.url_hash

            WHERE l.user_id = :user_id
            AND us.read_at IS NOT NULL
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
        ]);

        return intval($statement->fetchColumn());
    }
}
