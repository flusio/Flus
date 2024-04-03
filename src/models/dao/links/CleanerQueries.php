<?php

namespace App\models\dao\links;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the Cleaner.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait CleanerQueries
{
    /**
     * Delete links that are attached to no collections older than the given
     * date for the given user.
     */
    public static function deleteNotStoredOlderThan(string $user_id, \DateTimeImmutable $date): bool
    {
        $sql = <<<SQL
            DELETE FROM links

            USING links AS l

            LEFT JOIN links_to_collections AS lc
            ON l.id = lc.link_id

            WHERE links.id = l.id
            AND l.user_id = :user_id
            AND l.created_at < :date
            AND lc.link_id IS NULL;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute([
            ':user_id' => $user_id,
            ':date' => $date->format(Database\Column::DATETIME_FORMAT),
        ]);
    }

    /**
     * Delete links that are attached to feeds collections according to the
     * retention policy.
     *
     * The retention policy is defined by $keep_period (the number max of
     * months to keep links) and by $keep_maximum (no more links than this
     * number).
     *
     * An additional $keep_minimum policy allows to bypass the $keep_period in
     * order to keep a minimum number of links in feeds. Note that this value
     * MUST be smaller or equal to $keep_maximum. Unexpected behaviour may
     * happen otherwise and this method doesn't check the values.
     */
    public static function deleteFromFeeds(
        string $user_id,
        int $keep_period,
        int $keep_minimum,
        int $keep_maximum
    ): bool {
        if ($keep_period === 0 && $keep_maximum === 0) {
            // no retention policy, nothing to do
            return true;
        }

        // This SQL query is pretty complicated. The difficulty is to get old
        // enough or the excess of links while keeping enough links in each
        // feed. The trick is to count row numbers over a table temporarily
        // partitioned by collection_id and then filter this list to get the
        // links to delete.

        $parameters = [
            ':user_id' => $user_id,
        ];

        // The first sub-query is to select all the links ids with their
        // position in their own feed, ordered by their publication date
        // (newest first).
        // Thanks to https://stackoverflow.com/a/6064141 for the idea.
        $sql_select_links_with_position = <<<SQL
            SELECT
                lc.link_id,
                lc.created_at,
                ROW_NUMBER() OVER (
                    PARTITION BY lc.collection_id
                    ORDER BY lc.created_at DESC
                ) AS row_number
            FROM links_to_collections lc, collections c

            WHERE lc.collection_id = c.id

            AND c.user_id = :user_id
            AND c.type = 'feed'
        SQL;

        // The second subquery get the result of the first sub-query, and
        // keep:

        // 1. the old enough ones IF their position is after the minimum limit
        $period_clause = '';
        if ($keep_period > 0) {
            $retention_date = \Minz\Time::ago($keep_period, 'months');
            $parameters[':date'] = $retention_date->format(Database\Column::DATETIME_FORMAT);
            $parameters[':minimum'] = $keep_minimum;
            $period_clause = '(tmp.created_at < :date AND tmp.row_number > :minimum)';
        }

        // 2. links in excess (i.e. position is after the maximum limit)
        $maximum_clause = '';
        if ($keep_maximum > 0) {
            $parameters[':maximum'] = $keep_maximum;
            $maximum_clause = 'tmp.row_number > :maximum';

            if ($period_clause) {
                $maximum_clause = 'OR ' . $maximum_clause;
            }
        }

        $sql_links_to_delete = <<<SQL
            SELECT tmp.link_id FROM (
                {$sql_select_links_with_position}
            ) tmp

            WHERE
                {$period_clause}
                {$maximum_clause}
        SQL;

        // And finally, delete the links that have been selected by the
        // previous sub-query.
        $sql = <<<SQL
            DELETE FROM links
            WHERE id IN (
                {$sql_links_to_delete}
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($parameters);
    }
}
