<?php

namespace flusio\models\dao\collections;

/**
 * Add methods providing SQL queries specific to the Fetcher.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FetcherQueries
{
    /**
     * List (randomly) active feeds to be fetched.
     *
     * An active feed is a feed followed by at least one active user (i.e.
     * account has been validated)
     *
     * @param \DateTime $before
     * @param integer $limit
     *
     * @return array
     */
    public function listActiveFeedsToFetch($before, $limit)
    {
        $sql = <<<SQL
            SELECT c.*
            FROM collections c, followed_collections fc, users u

            WHERE c.type = 'feed'
            AND (
                c.feed_fetched_at <= :before
                OR c.feed_fetched_at IS NULL
            )

            AND c.id = fc.collection_id
            AND u.id = fc.user_id

            -- We prioritize feeds followed by active users. We ignore for now
            -- expired subscriptions, but it could be checked too.
            AND u.validated_at IS NOT NULL

            ORDER BY random()
            LIMIT :limit
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':before' => $before->format(\Minz\Model::DATETIME_FORMAT),
            ':limit' => $limit,
        ]);
        return $statement->fetchAll();
    }

    /**
     * List feeds that haven't been fetched for the longest time.
     *
     * @param \DateTime $before
     * @param integer $limit
     *
     * @return array
     */
    public function listOldestFeedsToFetch($before, $limit)
    {
        $sql = <<<SQL
            SELECT c.*
            FROM collections c

            WHERE c.type = 'feed'
            AND (
                c.feed_fetched_at <= :before
                OR c.feed_fetched_at IS NULL
            )

            ORDER BY feed_fetched_at NULLS FIRST
            LIMIT :limit
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':before' => $before->format(\Minz\Model::DATETIME_FORMAT),
            ':limit' => $limit,
        ]);
        return $statement->fetchAll();
    }
}
