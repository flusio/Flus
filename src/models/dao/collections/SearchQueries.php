<?php

namespace flusio\models\dao\collections;

/**
 * Add methods providing SQL queries specific to the search system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait SearchQueries
{
    /**
     * Return the list of feeds of the given user and with the given feed URLs.
     *
     * @param string $user_id
     * @param string[] $feed_urls
     *
     * @return array
     */
    public function listComputedFeedsByFeedUrls($feed_urls, $selected_computed_props)
    {
        if (!$feed_urls) {
            return [];
        }

        $urls_as_question_marks = array_fill(0, count($feed_urls), '?');
        $urls_where_statement = implode(', ', $urls_as_question_marks);

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.id) AS number_links
            FROM collections c

            LEFT JOIN links_to_collections lc
            ON lc.collection_id = c.id

            WHERE c.type = 'feed'
            AND c.feed_url IN ({$urls_where_statement})
            AND c.is_public = true

            GROUP BY c.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($feed_urls);
        return $statement->fetchAll();
    }
}
