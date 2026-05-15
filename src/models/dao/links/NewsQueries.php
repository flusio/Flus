<?php

namespace App\models\dao\links;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the News.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait NewsQueries
{
    /**
     * Return public links listed in followed collections of the given user,
     * ordered by publication date.
     *
     * @return self[]
     */
    public static function listFromFollowedCollections(string $user_id, int $max): array
    {
        $values = [
            ':user_id' => $user_id,
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
                c.id AS initial_collection_id
            FROM collections c, links_to_collections lc, followed_collections fc, links l

            WHERE fc.user_id = :user_id
            AND fc.collection_id = lc.collection_id

            AND lc.link_id = l.id
            AND lc.collection_id = c.id

            AND (
                (l.is_hidden = false AND c.is_public = true)
                OR c.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                )
            )

            AND NOT EXISTS (
                SELECT 1
                FROM links l_exclude, collections c_exclude, links_to_collections lc_exclude

                WHERE c_exclude.user_id = :user_id
                AND l_exclude.user_id = :user_id
                AND l_exclude.url_hash = l.url_hash

                AND (
                    c_exclude.type = 'news'
                    OR c_exclude.type = 'bookmarks'
                    OR c_exclude.type = 'read'
                    OR c_exclude.type = 'never'
                )

                AND lc_exclude.link_id = l_exclude.id
                AND lc_exclude.collection_id = c_exclude.id
            )

            AND l.user_id != :user_id

            AND lc.created_at >= :until_hard_limit
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
    public static function anyFromFollowedCollections(string $user_id): bool
    {
        $values = [
            ':user_id' => $user_id,
            ':until_hard_limit' => \Minz\Time::ago(1, 'year')->format(Database\Column::DATETIME_FORMAT),
            ':until_strict' => \Minz\Time::ago(1, 'day')->format(Database\Column::DATETIME_FORMAT),
            ':until_normal' => \Minz\Time::ago(1, 'week')->format(Database\Column::DATETIME_FORMAT),
        ];

        $sql = <<<SQL
            SELECT 1
            WHERE EXISTS (
                SELECT l.id
                FROM collections c, links_to_collections lc, followed_collections fc, links l

                WHERE fc.user_id = :user_id
                AND fc.collection_id = lc.collection_id

                AND lc.link_id = l.id
                AND lc.collection_id = c.id

                AND (
                    (l.is_hidden = false AND c.is_public = true)
                    OR c.user_id = :user_id
                    OR EXISTS (
                        SELECT 1 FROM collection_shares cs
                        WHERE cs.user_id = :user_id
                        AND cs.collection_id = c.id
                    )
                )

                AND NOT EXISTS (
                    SELECT 1
                    FROM links l_exclude, collections c_exclude, links_to_collections lc_exclude

                    WHERE c_exclude.user_id = :user_id
                    AND l_exclude.user_id = :user_id
                    AND l_exclude.url_hash = l.url_hash

                    AND (
                        c_exclude.type = 'news'
                        OR c_exclude.type = 'bookmarks'
                        OR c_exclude.type = 'read'
                        OR c_exclude.type = 'never'
                    )

                    AND lc_exclude.link_id = l_exclude.id
                    AND lc_exclude.collection_id = c_exclude.id
                )

                AND l.user_id != :user_id

                AND lc.created_at >= :until_hard_limit
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
