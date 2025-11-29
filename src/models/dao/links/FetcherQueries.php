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
}
