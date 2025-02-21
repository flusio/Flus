<?php

namespace App\models\dao\collections;

use Minz\Database;

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
     */
    public static function countCollections(): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'collection'
        SQL;

        $database = Database::get();
        $statement = $database->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of collections (type "collection").
     */
    public static function countCollectionsPublic(): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'collection'
            AND is_public = true
        SQL;

        $database = Database::get();
        $statement = $database->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of feeds (type "feed").
     */
    public static function countFeeds(): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM collections
            WHERE type = 'feed'
        SQL;

        $database = Database::get();
        $statement = $database->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of feeds (type "feed") indexed by the date - hour of
     * their next retrieval.
     *
     * @return array<string, int>
     */
    public static function countFeedsByHours(): array
    {
        $sql = <<<'SQL'
            SELECT TO_CHAR(feed_fetched_next_at, 'YYYY-MM-DD HH24') AS hour, COUNT(*) as count
            FROM collections
            WHERE type = 'feed'
            GROUP BY hour
        SQL;

        $database = Database::get();
        $statement = $database->query($sql);
        $result = $statement->fetchAll();

        $count_by_hours = [];
        foreach ($result as $row) {
            /** @var string */
            $hour = $row['hour'];
            $count_by_hours[$hour] = intval($row['count']);
        }

        ksort($count_by_hours);

        return $count_by_hours;
    }
}
