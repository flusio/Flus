<?php

namespace flusio\models\dao;

/**
 * Represent a link in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Link extends \Minz\DatabaseModel
{
    use BulkQueries;
    use LockQueries;
    use MediaQueries;
    use links\CleanerQueries;
    use links\DataExporterQueries;
    use links\FetcherQueries;
    use links\NewsQueries;
    use links\PocketQueries;
    use links\SearchQueries;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_filter(\flusio\models\Link::PROPERTIES, function ($declaration) {
            return !isset($declaration['computed']) || !$declaration['computed'];
        });
        parent::__construct('links', 'id', array_keys($properties));
    }

    /**
     * Return a link with its computed properties.
     *
     * @param array $values
     *     The conditions the link must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     *
     * @return array
     */
    public function findComputedBy($values, $selected_computed_props)
    {
        $parameters = [];

        // Note that publication date is usually computed by considering the
        // date of association with a collection. Without collection, we
        // consider its date of insertion in the database.
        $published_at_clause = '';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', l.created_at AS published_at';
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

        $where_statement_as_array = [];
        foreach ($values as $property => $parameter) {
            $parameters[] = $parameter;
            $where_statement_as_array[] = "{$property} = ?";
        }
        $where_statement = implode(' AND ', $where_statement_as_array);

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_comments_clause}
            FROM links l
            WHERE {$where_statement}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        $result = $statement->fetch();
        if ($result) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Return links of the given user with its computed properties.
     *
     * Links are sorted by published_at if the property is included, or by
     * created_at otherwise.
     *
     * Also, if unshared links are excluded, links are returned on the base of
     * their relation with collections. It means that published_at will be set
     * to the date of attachment of the related collection. If a link is
     * attached to multiple collections, it could potentially return the same
     * link several times with different published_at. However, the method
     * takes care of it and will return the link only once by taking the most
     * recent attachment.
     *
     * You may be affraid by this method and you would be right. This is the
     * price to pay to return not duplicated and ordered links with their
     * computed properties.
     *
     * @param string $user_id
     *     The user id the links must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array $options
     *     Custom options to filter links. Possible options are:
     *     - unshared (boolean, default to true), indicates if unshared links
     *       must be included. Shared links are visible and are included in one
     *       public collection at least.
     *     - offset (integer, default to 0), the offset for pagination
     *     - limit (integer|string, default to 'ALL') the limit for pagination
     *
     * @return array
     */
    public function listComputedByUserId($user_id, $selected_computed_props, $options = [])
    {
        $default_options = [
            'unshared' => true,
            'offset' => 0,
            'limit' => 'ALL',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
            ':offset' => $options['offset'],
        ];

        $published_at_clause = '';
        $order_by_clause = 'ORDER BY l.created_at DESC, l.id';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', l.created_at AS published_at';
            $order_by_clause = 'ORDER BY published_at DESC, l.id';
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

        $visibility_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (!$options['unshared']) {
            $visibility_clause = 'AND l.is_hidden = false';
            $join_clause = <<<SQL
                INNER JOIN links_to_collections lc
                ON lc.link_id = l.id

                INNER JOIN collections c
                ON lc.collection_id = c.id
                AND c.is_public = true
            SQL;

            if (in_array('published_at', $selected_computed_props)) {
                $published_at_clause = ', MAX(lc.created_at) AS published_at';
                $group_by_clause = 'GROUP BY l.id';
            }
        }

        $limit_clause = '';
        if ($options['limit'] !== 'ALL') {
            $limit_clause = 'LIMIT :limit';
            $parameters[':limit'] = $options['limit'];
        }

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_comments_clause}
            FROM links l

            {$join_clause}

            WHERE l.user_id = :user_id

            {$visibility_clause}

            {$group_by_clause}
            {$order_by_clause}
            OFFSET :offset
            {$limit_clause}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /**
     * Return links of the given collection with its computed properties.
     *
     * Links are sorted by published_at if the property is included, or by
     * created_at otherwise.
     *
     * @param string $collection_id
     *     The collection id the links must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array $options
     *     Custom options to filter links. Possible options are:
     *     - hidden (boolean, default to true), indicates if hidden links must be included
     *     - offset (integer, default to 0), the offset for pagination
     *     - limit (integer|string, default to 'ALL') the limit for pagination
     *
     * @return array
     */
    public function listComputedByCollectionId($collection_id, $selected_computed_props, $options = [])
    {
        $default_options = [
            'hidden' => true,
            'offset' => 0,
            'limit' => 'ALL',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':collection_id' => $collection_id,
            ':offset' => $options['offset'],
        ];

        $published_at_clause = '';
        $order_by_clause = 'ORDER BY l.created_at DESC, l.id';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', lc.created_at AS published_at';
            $order_by_clause = 'ORDER BY lc.created_at DESC, l.id';
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

        $visibility_clause = '';
        if (!$options['hidden']) {
            $visibility_clause = 'AND l.is_hidden = false';
        }

        $limit_clause = '';
        if ($options['limit'] !== 'ALL') {
            $limit_clause = 'LIMIT :limit';
            $parameters[':limit'] = $options['limit'];
        }

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_comments_clause}
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            {$visibility_clause}

            {$order_by_clause}
            OFFSET :offset
            {$limit_clause}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /**
     * Count links of the given collection.
     *
     * @param string $collection_id
     *     The collection id the links must match.
     * @param array $options
     *     Custom options to filter links. Possible option is:
     *     - hidden (boolean, default to true), indicates if hidden links must be included
     *
     * @return array
     */
    public function countByCollectionId($collection_id, $options = [])
    {
        $default_options = [
            'hidden' => true,
        ];
        $options = array_merge($default_options, $options);

        $visibility_clause = '';
        if (!$options['hidden']) {
            $visibility_clause = 'AND l.is_hidden = false';
        }

        $sql = <<<SQL
            SELECT COUNT(l.*)
            FROM links l, links_to_collections lc

            WHERE l.id = lc.link_id
            AND lc.collection_id = :collection_id

            {$visibility_clause}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection_id,
        ]);
        return intval($statement->fetchColumn());
    }

    /**
     * Return whether or not the given user id read the link URL.
     *
     * @param string $user_id
     * @param string $url
     *
     * @return boolean
     */
    public function isUrlReadByUserId($user_id, $url)
    {
        $sql = <<<'SQL'
            SELECT 1
            FROM links l, collections c, links_to_collections lc

            WHERE l.user_id = :user_id
            AND l.url_lookup = simplify_url(:url)

            AND c.type = 'read'

            AND lc.collection_id = c.id
            AND lc.link_id = l.id;
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':url' => $url,
        ]);
        return (bool)$statement->fetchColumn();
    }

    /**
     * Return an estimated number of links.
     *
     * This method have better performance than basic count but is less
     * precise.
     *
     * @see https://wiki.postgresql.org/wiki/Count_estimate
     *
     * @return integer
     */
    public function countEstimated()
    {
        $sql = <<<SQL
            SELECT reltuples AS count
            FROM pg_class
            WHERE relname = '{$this->table_name}';
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }
}
