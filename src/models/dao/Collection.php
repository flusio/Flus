<?php

namespace flusio\models\dao;

/**
 * Represent a collection of flusio in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collection extends \Minz\DatabaseModel
{
    use BulkQueries;
    use LockQueries;
    use collections\CleanerQueries;
    use collections\DiscoveryQueries;
    use collections\FetcherQueries;
    use collections\OpmlImportatorQueries;
    use collections\PocketQueries;
    use collections\SearchQueries;
    use collections\StatisticsQueries;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_filter(\flusio\models\Collection::PROPERTIES, function ($declaration) {
            return !isset($declaration['computed']) || !$declaration['computed'];
        });
        parent::__construct('collections', 'id', array_keys($properties));
    }

    /**
     * Return the list of collections attached to the given link.
     *
     * @param string $link_id
     *
     * @return array
     */
    public function listByLinkId($link_id)
    {
        $sql = <<<'SQL'
            SELECT c.*
            FROM collections c, links_to_collections lc

            WHERE lc.collection_id = c.id
            AND lc.link_id = :link_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':link_id' => $link_id,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return the collections of the given user with its computed properties.
     *
     * @param string $user_id
     *     The user id that the collections must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array $options
     *     Custom options to filter collections. Possible options are:
     *     - private (boolean, default to true), indicates if private
     *       collections must be included. If private are excluded and
     *       number_links property is selected, empty public collections are
     *       not returned either.
     *     - count_hidden (boolean, default to true), indicates if hidden links
     *       must be counted
     *
     * @return array
     */
    public function listComputedByUserId($user_id, $selected_computed_props, $options = [])
    {
        $default_options = [
            'private' => true,
            'count_hidden' => true,
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
        ];

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.*) AS number_links';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = c.id
            SQL;
            $group_by_clause = 'GROUP BY c.id';

            if (!$options['count_hidden']) {
                $number_links_clause = ', COUNT(l.*) AS number_links';

                $join_clause .= <<<SQL
                    \n
                    LEFT JOIN links l
                    ON lc.link_id = l.id
                    AND l.is_hidden = false
                SQL;
            }
        }

        $private_clause = '';
        $non_empty_clause = '';
        if (!$options['private']) {
            $private_clause = 'AND c.is_public = true';

            if (in_array('number_links', $selected_computed_props)) {
                $non_empty_clause = 'HAVING COUNT(lc.*) > 0';

                if (!$options['count_hidden']) {
                    $non_empty_clause = 'HAVING COUNT(l.*) > 0';
                }
            }
        }

        $sql = <<<SQL
            SELECT
                c.*
                {$number_links_clause}
            FROM collections c

            {$join_clause}

            WHERE c.user_id = :user_id
            AND c.type = 'collection'

            {$private_clause}

            {$group_by_clause}

            {$non_empty_clause}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /**
     * Return the collections followed by the given user with its computed properties.
     *
     * Only public collections are returned. That means if a user started to
     * follow a collection when it was public, if is_public is changed to
     * false, the user will not be able to see the collections anymore.
     *
     * @param string $user_id
     *     The id of the user who follows the collections.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array $options
     *     Custom options to filter collections. Possible options are:
     *     - type (string, either 'collection', 'feed' or 'all'), indicates
     *       what type of Collection must be returned. Count of links is
     *       optimized for "feed" collections so it may be interesting to call
     *       this method in two steps.
     *
     * @return array
     */
    public function listComputedFollowedByUserId($user_id, $selected_computed_props, $options = [])
    {
        $default_options = [
            'type' => 'all',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
        ];

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.*) AS number_links';
            $group_by_clause = 'GROUP BY c.id, fc.group_id';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = fc.collection_id
            SQL;

            if ($options['type'] !== 'feed') {
                // Joinning the links table is only required if we need to
                // check the is_hidden property. Because feeds contain only
                // public links, we can optimize the request in this specific
                // case.
                $number_links_clause = ', COUNT(l.*) AS number_links';
                $join_clause .= <<<SQL
                    LEFT JOIN links l
                    ON lc.link_id = l.id
                    AND l.is_hidden = false
                SQL;
            }
        }

        $type_clause = '';
        if ($options['type'] === 'collection') {
            $type_clause = "AND type = 'collection'";
        } elseif ($options['type'] === 'feed') {
            $type_clause = "AND type = 'feed'";
        }

        $time_filter_clause = '';
        if (in_array('time_filter', $selected_computed_props)) {
            $time_filter_clause = ', fc.time_filter AS time_filter';
        }

        $sql = <<<SQL
            SELECT
                c.*,
                fc.group_id
                {$number_links_clause}
                {$time_filter_clause}
            FROM collections c, followed_collections fc

            {$join_clause}

            WHERE fc.collection_id = c.id
            AND fc.user_id = :user_id

            AND c.is_public = true

            {$type_clause}

            {$group_by_clause}
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /**
     * Return whether the given user owns the given collections or not.
     *
     * @param string $user_id
     * @param string[] $collection_ids
     *
     * @return boolean True if all the ids exist
     */
    public function doesUserOwnCollections($user_id, $collection_ids)
    {
        if (empty($collection_ids)) {
            return true;
        }

        $matching_rows = $this->listBy([
            'id' => $collection_ids,
            'user_id' => $user_id,
        ]);
        return count($matching_rows) === count($collection_ids);
    }
}
