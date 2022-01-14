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
    use BulkHelper;
    use links\CleanerQueries;
    use links\DataExporterQueries;
    use links\FetcherQueries;
    use links\LockQueries;
    use links\NewsQueries;
    use links\PocketQueries;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Link::PROPERTIES);
        parent::__construct('links', 'id', $properties);
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
        $where_statement_as_array = [];
        foreach ($values as $property => $parameter) {
            $parameters[] = $parameter;
            $where_statement_as_array[] = "{$property} = ?";
        }
        $where_statement = implode(' AND ', $where_statement_as_array);

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

        $values = [
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
            $values[':limit'] = $options['limit'];
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
        $statement->execute($values);
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
     * Return whether the given user has read the URL or not.
     *
     * @param string $user_id
     * @param string $url
     *
     * @return boolean
     **/
    public function hasUserReadUrl($user_id, $url)
    {
        $sql = <<<SQL
            SELECT 1
            FROM links l, links_to_collections lc, collections c

            WHERE l.id = lc.link_id
            AND c.id = lc.collection_id

            AND c.user_id = :user_id
            AND l.url = :url

            AND c.type = 'read'
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':url' => $url,
        ]);
        $result = $statement->fetchColumn();
        return $result === 1;
    }
}
