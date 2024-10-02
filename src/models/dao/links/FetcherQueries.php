<?php

namespace App\models\dao\links;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the fetchers.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FetcherQueries
{
    /**
     * Return the list of url ids indexed by urls for the given collection.
     *
     * @param string $collection_id
     *
     * @return array<string, string>
     */
    public static function listUrlsToIdsByCollectionId(string $collection_id): array
    {
        $sql = <<<SQL
            SELECT l.url, l.id FROM links l, links_to_collections lc
            WHERE lc.link_id = l.id
            AND lc.collection_id = :collection_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Return the list of urls indexed by entry ids for the given collection.
     *
     * @return array<string, array{
     *     'id': string,
     *     'url': string,
     * }>
     */
    public static function listEntryIdsToUrlsByCollectionId(string $collection_id): array
    {
        $sql = <<<SQL
            SELECT l.feed_entry_id, l.id, l.url
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            ORDER BY lc.created_at DESC, l.id
            LIMIT 200
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);

        return $statement->fetchAll(\PDO::FETCH_UNIQUE);
    }

    /**
     * Return a list of links to fetch.
     *
     * The links with a fetched_at too close (a number of seconds depending on
     * the fetched_count value) are not returned.
     *
     * The list is limited by the $max parameter, and is randomly ordered.
     *
     * @return self[]
     */
    public static function listToFetch(int $max): array
    {
        $sql = <<<'SQL'
            SELECT * FROM links
            WHERE to_be_fetched = true
            AND (
                fetched_at IS NULL
                OR fetched_at < (?::timestamptz - interval '1 second' * (5 + pow(fetched_count, 4)))
            )
            ORDER BY random()
            LIMIT ?
        SQL;

        $now = \Minz\Time::now();

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            $now->format(Database\Column::DATETIME_FORMAT),
            $max,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return the number of links to fetch.
     */
    public static function countToFetch(): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM links
            WHERE to_be_fetched = true
            AND (
                fetched_at IS NULL
                OR fetched_at < (?::timestamptz - interval '1 second' * (5 + pow(fetched_count, 4)))
            )
        SQL;

        $now = \Minz\Time::now();

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            $now->format(Database\Column::DATETIME_FORMAT),
        ]);

        return intval($statement->fetchColumn());
    }
}
