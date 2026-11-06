<?php

namespace App\models\link_lists;

use App\models\CollectionToTopic;
use App\models\Topic;
use Minz\Database;

/**
 * Add the topics of a collection, which allow to discover the public collections.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Discoverable
{
    /**
     * Return the topics attached to the current collection.
     *
     * @return Topic[]
     */
    public function topics(): array
    {
        return Topic::listByCollection($this);
    }

    /**
     * Set the collection's topics.
     *
     * @param Topic[] $topics
     */
    public function setTopics(array $topics): void
    {
        CollectionToTopic::set($this, $topics, $this->topics());
    }

    /**
     * Return the list of public collections attached to the given topic.
     *
     * @return self[]
     */
    public static function listPublicByTopicWithNumberLinks(
        Topic $topic,
        int $pagination_offset,
        int $pagination_limit
    ): array {
        $sql = <<<'SQL'
            SELECT c.*, COUNT(*) AS number_links
            FROM collections c, collections_to_topics ct, links_to_collections lc, links l

            WHERE c.id = ct.collection_id
            AND c.id = lc.collection_id
            AND l.id = lc.link_id

            AND c.type = 'collection'
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
            ':topic_id' => $topic->id,
            ':offset' => $pagination_offset,
            ':limit' => $pagination_limit,
        ]);
        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Count the public collections attached to the given topic.
     */
    public static function countPublicByTopic(Topic $topic): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(DISTINCT c.id)
            FROM collections c, collections_to_topics ct, links_to_collections lc, links l

            WHERE c.id = ct.collection_id
            AND c.id = lc.collection_id
            AND l.id = lc.link_id

            AND c.type = 'collection'
            AND c.is_public = true
            AND l.is_hidden = false
            AND ct.topic_id = :topic_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':topic_id' => $topic->id,
        ]);
        return intval($statement->fetchColumn());
    }
}
