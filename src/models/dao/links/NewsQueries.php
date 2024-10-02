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
                'collection' AS source_news_type,
                c.id AS source_news_resource_id
            FROM collections c, links_to_collections lc, followed_collections fc, links l

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
     * Mark the relevant links to be grouped by sources in the given collection.
     *
     * Links are grouped if there are several links in the given collection
     * corresponding to the same source and the same day.
     *
     * The passed collection_id must correspond to a "news" collection. For
     * now, it's passed this way to improve performance and to simplify a bit
     * the SQL request.
     */
    public static function groupLinksBySources(string $collection_id): bool
    {
        $sql = <<<SQL
            UPDATE links
            SET group_by_source = true
            WHERE links.id IN (
                -- Create a "temporary table" to select the available sources
                -- from the given collection (e.g. sources that are
                -- referenced by more than 1 link).
                WITH sources AS (
                    SELECT date_trunc('day', slc.created_at) AS published_day,
                           sl.source_type,
                           sl.source_resource_id
                    FROM links sl, links_to_collections slc

                    WHERE sl.id = slc.link_id
                    AND slc.collection_id = :collection_id

                    GROUP BY published_day, sl.source_type, sl.source_resource_id
                    HAVING COUNT(sl.id) > 1
                )

                -- Select the ids of links which have a source corresponding to
                -- one of the selected sources.
                SELECT l.id
                FROM links l, links_to_collections lc, sources s

                WHERE l.id = lc.link_id
                AND lc.collection_id = :collection_id

                AND l.source_type = s.source_type
                AND l.source_resource_id = s.source_resource_id
                AND date_trunc('day', lc.created_at) = s.published_day
            );
        SQL;

        $parameters = [
            ':collection_id' => $collection_id,
        ];

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($parameters);
    }
}
