<?php

namespace App\models\links;

use App\models\Journal;
use Minz\Database;

/**
 * Add methods to fill and manipulate the links of a journal.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait InJournal
{
    /**
     * Return the links that can be added to the given journal, ordered by
     * publication date.
     *
     * The candidates are the links published in the collections followed by
     * the owner of the journal. A link is only considered if the owner can
     * see it: the collection must be public, owned by the owner or shared
     * with them.
     *
     * @return self[]
     */
    public static function listCandidatesForJournal(Journal $journal, int $max): array
    {
        $values = [
            ':user_id' => $journal->owner()->id,
            ':until_hard_limit' => \Minz\Time::ago(1, 'year')->format(Database\Column::DATETIME_FORMAT),
            ':until_strict' => \Minz\Time::ago(1, 'day')->format(Database\Column::DATETIME_FORMAT),
            ':until_normal' => \Minz\Time::ago(1, 'week')->format(Database\Column::DATETIME_FORMAT),
            ':limit' => $max,
        ];

        // The candidates are selected first, from the follows of the user. The
        // lower date limit of each follow is computed as a single expression,
        // so PostgreSQL can use it as a bound of the links_to_collections
        // index. The candidates are sorted by publication date, so the main
        // query stops as soon as it reaches the limit.
        // The CTE is materialized because PostgreSQL badly estimates the
        // number of candidates otherwise.
        $sql = <<<SQL
            WITH candidates AS MATERIALIZED (
                SELECT lc.link_id, lc.collection_id, lc.created_at
                FROM followed_collections fc, links_to_collections lc

                WHERE fc.user_id = :user_id
                AND fc.collection_id = lc.collection_id

                AND fc.time_filter IN ('strict', 'normal', 'all')
                AND lc.created_at >= GREATEST(
                    :until_hard_limit,
                    CASE fc.time_filter
                        WHEN 'strict' THEN :until_strict
                        WHEN 'normal' THEN :until_normal
                        WHEN 'all' THEN fc.created_at - INTERVAL '1 week'
                    END
                )

                ORDER BY lc.created_at DESC
            )

            SELECT
                l.url_hash,
                l.*,
                ca.created_at AS published_at,
                co.id AS source_id
            FROM collections co, candidates ca, links l

            LEFT JOIN url_statuses us ON us.user_id = :user_id AND us.url_hash = l.url_hash

            WHERE ca.link_id = l.id
            AND ca.collection_id = co.id

            AND us.read_at IS NULL
            AND us.read_later_at IS NULL
            AND us.dismissed_at IS NULL

            AND (
                (l.is_hidden = false AND co.is_public = true)
                OR co.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = co.id
                )
            )

            AND l.user_id IS DISTINCT FROM :user_id

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
     * Return whether there are any links that can be added to the given
     * journal.
     *
     * @see self::listCandidatesForJournal
     */
    public static function anyCandidateForJournal(Journal $journal): bool
    {
        $values = [
            ':user_id' => $journal->owner()->id,
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

                AND fc.time_filter IN ('strict', 'normal', 'all')
                AND lc.created_at >= GREATEST(
                    :until_hard_limit,
                    CASE fc.time_filter
                        WHEN 'strict' THEN :until_strict
                        WHEN 'normal' THEN :until_normal
                        WHEN 'all' THEN fc.created_at - INTERVAL '1 week'
                    END
                )
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);

        return $statement->fetch() !== false;
    }

    /**
     * Mark the relevant links to be grouped by sources in the given journal.
     *
     * Links are grouped if there are several links in the journal
     * corresponding to the same source and the same day.
     */
    public static function groupLinksBySources(Journal $journal): bool
    {
        $sql = <<<SQL
            UPDATE links
            SET group_by_source = true
            WHERE links.id IN (
                -- Create a "temporary table" to select the available sources
                -- from the journal (e.g. sources that are referenced by more
                -- than 1 link).
                WITH sources AS (
                    SELECT date_trunc('day', slc.created_at) AS published_day,
                           sl.source_id
                    FROM links sl, links_to_collections slc

                    WHERE sl.id = slc.link_id
                    AND slc.collection_id = :collection_id

                    GROUP BY published_day, sl.source_id
                    HAVING COUNT(sl.id) > 1
                )

                -- Select the ids of links which have a source corresponding to
                -- one of the selected sources.
                SELECT l.id
                FROM links l, links_to_collections lc, sources s

                WHERE l.id = lc.link_id
                AND lc.collection_id = :collection_id

                AND l.source_id = s.source_id
                AND date_trunc('day', lc.created_at) = s.published_day
            );
        SQL;

        $parameters = [
            ':collection_id' => $journal->id,
        ];

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($parameters);
    }
}
