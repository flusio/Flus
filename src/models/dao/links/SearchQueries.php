<?php

namespace flusio\models\dao\links;

/**
 * Add methods providing SQL queries specific to the search system.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait SearchQueries
{
    /**
     * Search links of the given user with its computed properties.
     *
     * This method uses PGSQL full text search feature.
     *
     * Links are sorted by relevancy against the search query. Note that the
     * computed property `search_rank` is always returned.
     *
     * @see https://www.postgresql.org/docs/current/textsearch.html
     *
     * @param string $query
     *     The query to search for.
     * @param string $user_id
     *     The user id the links must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array $options
     *     Custom options to filter links. Possible options are:
     *     - offset (integer, default to 0), the offset for pagination
     *     - limit (integer|string, default to 'ALL') the limit for pagination
     *     - context_user_id (string, default to ''), is_read refers to this user id
     *
     * @return array
     */
    public function listComputedByQueryAndUserId($query, $user_id, $selected_computed_props, $options = [])
    {
        $default_options = [
            'offset' => 0,
            'limit' => 'ALL',
            'context_user_id' => '',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
            ':query' => $query,
            ':offset' => $options['offset'],
        ];

        $published_at_clause = '';
        $order_by_clause = 'ORDER BY search_rank DESC, l.created_at DESC, l.id';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', l.created_at AS published_at';
            $order_by_clause = 'ORDER BY search_rank DESC, published_at DESC, l.id';
        }

        $number_comments_clause = '';
        if (in_array('number_comments', $selected_computed_props)) {
            $number_comments_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM messages m
                    WHERE m.link_id = l.id
                ) AS number_comments
            SQL;
        }

        $read_links_clause = '';
        $is_read_clause = '';
        if (in_array('is_read', $selected_computed_props)) {
            $read_links_clause = <<<'SQL'
                WITH read_links AS (
                    SELECT DISTINCT l_read.url
                    FROM links l_read, collections c_read, links_to_collections lc_read

                    WHERE c_read.user_id = :context_user_id
                    AND c_read.type = 'read'

                    AND lc_read.link_id = l_read.id
                    AND lc_read.collection_id = c_read.id
                )
            SQL;

            $is_read_clause = <<<'SQL'
                , (
                    SELECT true FROM read_links
                    WHERE read_links.url = l.url
                ) AS is_read
            SQL;

            $parameters[':context_user_id'] = $options['context_user_id'];
        }

        $limit_clause = '';
        if ($options['limit'] !== 'ALL') {
            $limit_clause = 'LIMIT :limit';
            $parameters[':limit'] = $options['limit'];
        }

        $sql = <<<SQL
            {$read_links_clause}

            SELECT
                l.*,
                ts_rank(search_index, query, 2) AS search_rank
                {$published_at_clause}
                {$number_comments_clause}
                {$is_read_clause}
            FROM links l, plainto_tsquery('french', :query) AS query

            WHERE l.user_id = :user_id
            AND search_index @@ query

            {$order_by_clause}
            OFFSET :offset
            {$limit_clause}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /**
     * Return the number of links matching with query and user_id.
     *
     * @param string $query
     *     The query to search for.
     * @param string $user_id
     *     The user id the links must match.
     *
     * @return integer
     */
    public function countByQueryAndUserId($query, $user_id)
    {
        $parameters = [
            ':user_id' => $user_id,
            ':query' => $query,
        ];

        $sql = <<<SQL
            SELECT COUNT(l.id)
            FROM links l, plainto_tsquery('french', :query) AS query

            WHERE l.user_id = :user_id
            AND search_index @@ query
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return intval($statement->fetchColumn());
    }
}
