<?php

namespace flusio\models\dao\collections;

use Minz\Database;

/**
 * Add methods providing SQL queries specific to the discovery system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait DiscoveryQueries
{
    /**
     * Return the list of public collections attached to the given topic.
     *
     * @return self[]
     */
    public static function listPublicByTopicIdWithNumberLinks(
        string $topic_id,
        int $pagination_offset,
        int $pagination_limit
    ): array {
        $sql = <<<'SQL'
            SELECT c.*, COUNT(lc.*) AS number_links
            FROM collections c, collections_to_topics ct, links_to_collections lc, links l

            WHERE c.id = ct.collection_id
            AND c.id = lc.collection_id
            AND l.id = lc.link_id

            AND c.is_public = true
            AND l.is_hidden = false
            AND ct.topic_id = :topic_id

            GROUP BY c.id

            ORDER BY c.name
            OFFSET :offset
            LIMIT :limit
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':topic_id' => $topic_id,
            ':offset' => $pagination_offset,
            ':limit' => $pagination_limit,
        ]);
        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Count the public collections attached to the given topic.
     */
    public static function countPublicByTopicId(string $topic_id): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(DISTINCT c.id)
            FROM collections c, collections_to_topics ct, links_to_collections lc, links l

            WHERE c.id = ct.collection_id
            AND c.id = lc.collection_id
            AND l.id = lc.link_id

            AND c.is_public = true
            AND l.is_hidden = false
            AND ct.topic_id = :topic_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':topic_id' => $topic_id,
        ]);
        return intval($statement->fetchColumn());
    }
}
