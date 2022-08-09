<?php

namespace flusio\models\dao\links;

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
     *
     * @param string $user_id
     * @param \DateTime $date
     *
     * @return boolean True on success
     */
    public function deleteNotStoredOlderThan($user_id, $date)
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

        $statement = $this->prepare($sql);
        return $statement->execute([
            ':user_id' => $user_id,
            ':date' => $date->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }

    /**
     * Delete links that are attached to feeds collections with a publication
     * date older than the given date for the given user.
     *
     * @param string $user_id
     * @param \DateTime $date
     * @param integer $keep_minimum
     *
     * @return boolean True on success
     */
    public function deleteFromFeedsOlderThan($user_id, $date, $keep_minimum)
    {
        // This SQL query is pretty complicated. The difficulty is to get old
        // links while keeping enough links in each feed. The trick is to count
        // row numbers over a table temporarily partitioned by collection_id and
        // then filter this list to get the links to delete.

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
        // keep only the old enough ones IF their position is after the minimum
        // limit (so we are sure to keep at least the "minimum" number of links
        // in each feed).
        $sql_links_to_delete = <<<SQL
            SELECT tmp.link_id FROM (
                {$sql_select_links_with_position}
            ) tmp

            WHERE tmp.created_at < :date
            AND tmp.row_number > :minimum
        SQL;

        // And finally, delete the links that have been selected by the
        // previous sub-query.
        $sql = <<<SQL
            DELETE FROM links
            WHERE id IN (
                {$sql_links_to_delete}
            )
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute([
            ':user_id' => $user_id,
            ':date' => $date->format(\Minz\Model::DATETIME_FORMAT),
            ':minimum' => $keep_minimum,
        ]);
    }
}
