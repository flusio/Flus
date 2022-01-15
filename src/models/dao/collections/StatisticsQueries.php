<?php

namespace flusio\models\dao\collections;

/**
 * Add methods providing SQL queries specific to the statistics.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait StatisticsQueries
{
    /**
     * Return the number of collections (type "collection").
     *
     * @return integer
     */
    public function countCollections()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'collection'
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of collections (type "collection").
     *
     * @return integer
     */
    public function countCollectionsPublic()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'collection'
            AND is_public = true
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of feeds (type "feed").
     *
     * @return integer
     */
    public function countFeeds()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'feed'
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of feeds (type "feed") indexed by the hour of their
     * last fetch.
     *
     * @return integer[]
     */
    public function countFeedsByHours()
    {
        $sql = <<<'SQL'
            SELECT TO_CHAR(feed_fetched_at, 'HH24') AS hour, COUNT(*) as count
            FROM collections
            WHERE type = 'feed'
            GROUP BY hour
        SQL;

        $statement = $this->query($sql);
        $result = $statement->fetchAll();
        $count_by_hours = [];
        foreach ($result as $row) {
            $count_by_hours[$row['hour']] = intval($row['count']);
        }
        ksort($count_by_hours);
        return $count_by_hours;
    }
}
