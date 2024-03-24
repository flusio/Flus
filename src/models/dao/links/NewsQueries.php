<?php

namespace flusio\models\dao\links;

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
     * Return links listed in bookmarks of the given user, ordered randomly.
     *
     * @return self[]
     */
    public static function listFromBookmarksForNews(
        string $user_id,
        ?int $min_duration,
        ?int $max_duration,
    ): array {
        $where_placeholder = '';
        $values = [
            ':user_id' => $user_id,
        ];

        if ($min_duration !== null) {
            $where_placeholder .= 'AND l.reading_time >= :min_duration ';
            $values[':min_duration'] = $min_duration;
        }

        if ($max_duration !== null) {
            $where_placeholder .= 'AND l.reading_time < :max_duration ';
            $values[':max_duration'] = $max_duration;
        }

        $sql = <<<SQL
            SELECT l.*, lc.created_at AS published_at, 'bookmarks' AS via_news_type
            FROM links l, collections c, links_to_collections lc

            WHERE lc.link_id = l.id
            AND lc.collection_id = c.id

            AND c.user_id = :user_id
            AND l.user_id = :user_id

            AND c.type = 'bookmarks'

            {$where_placeholder}

            GROUP BY l.id, lc.created_at

            ORDER BY random()
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return public links listed in followed collections of the given user,
     * ordered by publication date. Links with a matching url in bookmarks or
     * read list are not returned.
     *
     * @return self[]
     */
    public static function listFromFollowedCollectionsForNews(
        string $user_id,
        ?int $min_duration,
        ?int $max_duration,
    ): array {
        $where_placeholder = '';
        $values = [
            ':user_id' => $user_id,
        ];

        if ($min_duration !== null) {
            $where_placeholder .= 'AND l.reading_time >= :min_duration ';
            $values[':min_duration'] = $min_duration;
        }

        if ($max_duration !== null) {
            $where_placeholder .= 'AND l.reading_time < :max_duration ';
            $values[':max_duration'] = $max_duration;
        }

        $where_placeholder .= <<<'SQL'
            AND (
                (fc.time_filter = 'strict' AND lc.created_at >= :until_strict) OR
                (fc.time_filter = 'normal' AND lc.created_at >= :until_normal) OR
                (fc.time_filter = 'all' AND lc.created_at >= fc.created_at - INTERVAL '3 days')
            )
        SQL;
        $values[':until_strict'] = \Minz\Time::ago(1, 'day')->format(Database\Column::DATETIME_FORMAT);
        $values[':until_normal'] = \Minz\Time::ago(3, 'days')->format(Database\Column::DATETIME_FORMAT);

        $sql = <<<SQL
            WITH excluded_links AS (
                SELECT l_exclude.id, l_exclude.url_lookup
                FROM links l_exclude, collections c_exclude, links_to_collections lc_exclude

                WHERE c_exclude.user_id = :user_id
                AND (
                    c_exclude.type = 'news'
                    OR c_exclude.type = 'bookmarks'
                    OR c_exclude.type = 'read'
                    OR c_exclude.type = 'never'
                )

                AND lc_exclude.link_id = l_exclude.id
                AND lc_exclude.collection_id = c_exclude.id
            )

            SELECT l.*, lc.created_at AS published_at, 'collection' AS via_news_type, c.id AS via_news_resource_id
            FROM collections c, links_to_collections lc, followed_collections fc, links l

            LEFT JOIN excluded_links
            ON excluded_links.url_lookup = l.url_lookup

            WHERE fc.user_id = :user_id
            AND fc.collection_id = lc.collection_id

            AND lc.link_id = l.id
            AND lc.collection_id = c.id

            AND (
                (l.is_hidden = false AND c.is_public = true) OR
                EXISTS (
                    SELECT 1 FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                )
            )

            AND l.user_id != :user_id

            AND excluded_links.id IS NULL

            {$where_placeholder}

            GROUP BY l.id, lc.created_at, c.id

            ORDER BY lc.created_at DESC, l.id
            LIMIT 100
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
