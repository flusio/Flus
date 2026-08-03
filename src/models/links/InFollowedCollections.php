<?php

namespace App\models\links;

use App\models\User;
use Minz\Database;

/**
 * Add methods to list the links published in the collections that a user
 * follows.
 *
 * These links are the ones that feed the journal of the user. A link is only
 * considered if the user can see it: the collection must be public, owned by
 * the user or shared with them.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InFollowedCollections
{
    /**
     * Return public links listed in followed collections of the given user,
     * ordered by publication date.
     *
     * @return self[]
     */
    public static function listFromFollowedCollections(User $user, int $max): array
    {
        $values = [
            ':user_id' => $user->id,
            ':until_hard_limit' => \Minz\Time::ago(1, 'year')->format(Database\Column::DATETIME_FORMAT),
            ':until_strict' => \Minz\Time::ago(1, 'day')->format(Database\Column::DATETIME_FORMAT),
            ':until_normal' => \Minz\Time::ago(1, 'week')->format(Database\Column::DATETIME_FORMAT),
            ':limit' => $max,
        ];

        $sql = <<<SQL
            SELECT
                l.url_hash,
                l.*,
                lc.created_at AS published_at,
                c.id AS source_id
            FROM collections c, links_to_collections lc, followed_collections fc, links l

            LEFT JOIN url_statuses us ON us.user_id = :user_id AND us.url_hash = l.url_hash

            WHERE fc.user_id = :user_id
            AND fc.collection_id = lc.collection_id

            AND lc.link_id = l.id
            AND lc.collection_id = c.id

            AND us.read_at IS NULL
            AND us.read_later_at IS NULL
            AND us.dismissed_at IS NULL

            AND (
                (l.is_hidden = false AND c.is_public = true)
                OR c.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                )
            )

            AND l.user_id IS DISTINCT FROM :user_id

            AND lc.created_at >= :until_hard_limit
            AND fc.time_filter != 'none'
            AND (
                (fc.time_filter = 'strict' AND lc.created_at >= :until_strict) OR
                (fc.time_filter = 'normal' AND lc.created_at >= :until_normal) OR
                (fc.time_filter = 'all' AND lc.created_at >= fc.created_at - INTERVAL '1 week')
            )

            ORDER BY published_at DESC, l.id

            LIMIT :limit
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);

        // Get the results indexed by the url_hash (i.e. the first column)
        $results = $statement->fetchAll(\PDO::FETCH_UNIQUE);

        return self::fromDatabaseRows($results);
    }

    /**
     * Return whether there are any public links listed in followed collections
     * of the given user.
     */
    public static function anyFromFollowedCollections(User $user): bool
    {
        $values = [
            ':user_id' => $user->id,
            ':until_hard_limit' => \Minz\Time::ago(1, 'year')->format(Database\Column::DATETIME_FORMAT),
            ':until_strict' => \Minz\Time::ago(1, 'day')->format(Database\Column::DATETIME_FORMAT),
            ':until_normal' => \Minz\Time::ago(1, 'week')->format(Database\Column::DATETIME_FORMAT),
        ];

        $sql = <<<SQL
            SELECT 1
            WHERE EXISTS (
                SELECT l.id
                FROM collections c, links_to_collections lc, followed_collections fc, links l

                LEFT JOIN url_statuses us ON us.user_id = :user_id AND us.url_hash = l.url_hash

                WHERE fc.user_id = :user_id
                AND fc.collection_id = lc.collection_id

                AND lc.link_id = l.id
                AND lc.collection_id = c.id

                AND us.read_at IS NULL
                AND us.read_later_at IS NULL
                AND us.dismissed_at IS NULL

                AND (
                    (l.is_hidden = false AND c.is_public = true)
                    OR c.user_id = :user_id
                    OR EXISTS (
                        SELECT 1 FROM collection_shares cs
                        WHERE cs.user_id = :user_id
                        AND cs.collection_id = c.id
                    )
                )

                AND l.user_id IS DISTINCT FROM :user_id

                AND lc.created_at >= :until_hard_limit
                AND fc.time_filter != 'none'
                AND (
                    (fc.time_filter = 'strict' AND lc.created_at >= :until_strict) OR
                    (fc.time_filter = 'normal' AND lc.created_at >= :until_normal) OR
                    (fc.time_filter = 'all' AND lc.created_at >= fc.created_at - INTERVAL '1 week')
                )
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);

        return $statement->fetch() !== false;
    }
}
