<?php

namespace flusio\models\dao\links;

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
     * @return array
     */
    public function listUrlsToIdsByCollectionId($collection_id)
    {
        $sql = <<<SQL
            SELECT l.id, l.url FROM links l, links_to_collections lc
            WHERE lc.link_id = l.id
            AND lc.collection_id = :collection_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);

        $ids_by_urls = [];
        foreach ($statement->fetchAll() as $row) {
            $ids_by_urls[$row['url']] = $row['id'];
        }
        return $ids_by_urls;
    }

    /**
     * Return the list of urls indexed by entry ids for the given collection.
     *
     * @param string $collection_id
     *
     * @return array
     */
    public function listEntryIdsToUrlsByCollectionId($collection_id)
    {
        $sql = <<<SQL
            SELECT l.id, l.url, l.feed_entry_id
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            ORDER BY lc.created_at DESC, l.id
            LIMIT 200
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);

        $urls_by_entry_ids = [];
        foreach ($statement->fetchAll() as $row) {
            $urls_by_entry_ids[$row['feed_entry_id']] = [
                'url' => $row['url'],
                'id' => $row['id'],
            ];
        }
        return $urls_by_entry_ids;
    }

    /**
     * Return a list of links to fetch (fetched_at is null, or fetched_code is in error).
     *
     * Links in error are not returned if their fetched_count is greater than
     * 25 or if fetched_at is too close (a number of seconds depending on the
     * fetched_count value).
     *
     * @param integer $max_number
     *
     * @return array
     */
    public function listToFetch($max_number)
    {
        $sql = <<<SQL
            SELECT * FROM links
            WHERE fetched_at IS NULL
            OR (
                (fetched_code < 200 OR fetched_code >= 300)
                AND fetched_count <= 25
                AND fetched_at < (?::timestamptz - interval '1 second' * (5 + pow(fetched_count, 4)))
            )
            ORDER BY random()
            LIMIT ?
        SQL;

        $now = \Minz\Time::now();
        $statement = $this->prepare($sql);
        $statement->execute([
            $now->format(\Minz\Model::DATETIME_FORMAT),
            $max_number,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return the number of links to fetch.
     *
     * @return integer
     */
    public function countToFetch()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM links
            WHERE fetched_at IS NULL
            OR (
                (fetched_code < 200 OR fetched_code >= 300)
                AND fetched_count <= 25
                AND fetched_at < (?::timestamptz - interval '1 second' * (5 + pow(fetched_count, 4)))
            )
        SQL;

        $now = \Minz\Time::now();
        $statement = $this->prepare($sql);
        $statement->execute([$now->format(\Minz\Model::DATETIME_FORMAT)]);
        return intval($statement->fetchColumn());
    }
}
