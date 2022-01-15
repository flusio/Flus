<?php

namespace flusio\models\dao\collections;

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
     * @param string $topic_id
     * @param integer $pagination_offset
     * @param integer $pagination_limit
     *
     * @return array
     */
    public function listPublicByTopicIdWithNumberLinks($topic_id, $pagination_offset, $pagination_limit)
    {
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

        $statement = $this->prepare($sql);
        $statement->execute([
            ':topic_id' => $topic_id,
            ':offset' => $pagination_offset,
            ':limit' => $pagination_limit,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Count the public collections attached to the given topic.
     *
     * @param string $topic_id
     *
     * @return integer
     */
    public function countPublicByTopicId($topic_id)
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

        $statement = $this->prepare($sql);
        $statement->execute([
            ':topic_id' => $topic_id,
        ]);
        return intval($statement->fetchColumn());
    }
}
