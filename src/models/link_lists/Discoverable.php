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
     * Return the list of public collections attached to the given topic and
     * containing at least one visible (i.e. not hidden) link.
     *
     * @return self[]
     */
    public static function listPublicByTopic(
        Topic $topic,
        int $pagination_offset,
        int $pagination_limit
    ): array {
        $sql = <<<'SQL'
            SELECT c.*
            FROM collections c, collections_to_topics ct

            WHERE c.id = ct.collection_id

            AND c.type = 'collection'
            AND c.is_public = true
            AND ct.topic_id = :topic_id
            AND EXISTS (
                SELECT 1
                FROM links_to_collections lc, links l

                WHERE lc.collection_id = c.id
                AND lc.link_id = l.id
                AND l.is_hidden = false
            )

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
     * Count the public collections attached to the given topic and containing
     * at least one visible (i.e. not hidden) link.
     */
    public static function countPublicByTopic(Topic $topic): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*)
            FROM collections c, collections_to_topics ct

            WHERE c.id = ct.collection_id

            AND c.type = 'collection'
            AND c.is_public = true
            AND ct.topic_id = :topic_id
            AND EXISTS (
                SELECT 1
                FROM links_to_collections lc, links l

                WHERE lc.collection_id = c.id
                AND lc.link_id = l.id
                AND l.is_hidden = false
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':topic_id' => $topic->id,
        ]);
        return intval($statement->fetchColumn());
    }
}
