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
        $properties = array_keys(\flusio\models\Collection::PROPERTIES);
        parent::__construct('collections', 'id', $properties);
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
     * Return the list of public collections of the given user.
     *
     * @param string $user_id
     * @param boolean $count_hidden_links
     *     Indicate if number_links should include hidden links
     *
     * @return array
     */
    public function listPublicByUserIdWithNumberLinks($user_id, $count_hidden_links)
    {
        $is_hidden_placeholder = '';
        if (!$count_hidden_links) {
            $is_hidden_placeholder = 'AND l.is_hidden = false';
        }

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.*) AS number_links
            FROM collections c, links_to_collections lc, links l

            WHERE c.id = lc.collection_id
            AND l.id = lc.link_id

            AND c.is_public = true
            AND c.type = 'collection'
            {$is_hidden_placeholder}

            AND c.user_id = :user_id

            GROUP BY c.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
        ]);
        return $statement->fetchAll();
    }

    /**
     * Return the collections of the given user and in the given group.
     *
     * If group id is null, it returns the collections in no groups.
     *
     * @param string $user_id
     * @param string $group_id
     *
     * @return array
     */
    public function listByUserIdAndGroupIdWithNumberLinks($user_id, $group_id)
    {
        $values = [':user_id' => $user_id];

        if ($group_id) {
            $group_placeholder = 'AND c.group_id = :group_id';
            $values[':group_id'] = $group_id;
        } else {
            $group_placeholder = 'AND c.group_id IS NULL';
        }

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.id) AS number_links
            FROM collections c

            LEFT JOIN links_to_collections lc
            ON lc.collection_id = c.id

            WHERE c.user_id = :user_id
            AND c.type = 'collection'
            {$group_placeholder}

            GROUP BY c.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return all the collections of the given user with their number of links.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listForLinksPage($user_id)
    {
        $values = [':user_id' => $user_id];

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.id) AS number_links
            FROM collections c

            LEFT JOIN links_to_collections lc
            ON lc.collection_id = c.id

            WHERE c.user_id = :user_id
            AND c.type = 'collection'

            GROUP BY c.id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return all the followed collections of the given user with their number
     * of visible links.
     *
     * @param string $user_id
     *
     * @return array
     */
    public function listForFeedsPage($user_id)
    {
        $values = [':user_id' => $user_id];

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.id) AS number_links, fc.group_id
            FROM collections c, followed_collections fc

            LEFT JOIN links_to_collections lc
            ON lc.collection_id = fc.collection_id

            LEFT JOIN links l
            ON lc.link_id = l.id
            AND l.is_hidden = false

            WHERE fc.collection_id = c.id
            AND fc.user_id = :user_id

            AND c.is_public = true

            GROUP BY c.id, fc.group_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        return $statement->fetchAll();
    }

    /**
     * Return the followed collections of the given user and in the given group.
     *
     * If group id is null, it returns the collections in no groups.
     *
     * @param string $user_id
     * @param string $group_id
     *
     * @return array
     */
    public function listFollowedByUserIdAndGroupIdWithNumberLinks($user_id, $group_id)
    {
        $values = [':user_id' => $user_id];

        if ($group_id) {
            $group_placeholder = 'AND fc.group_id = :group_id';
            $values[':group_id'] = $group_id;
        } else {
            $group_placeholder = 'AND fc.group_id IS NULL';
        }

        $sql = <<<SQL
            SELECT c.*, COUNT(lc.id) AS number_links, fc.group_id
            FROM collections c, followed_collections fc

            LEFT JOIN links_to_collections lc
            ON lc.collection_id = fc.collection_id

            LEFT JOIN links l
            ON lc.link_id = l.id
            AND l.is_hidden = false

            WHERE fc.collection_id = c.id
            AND fc.user_id = :user_id

            AND c.is_public = true
            {$group_placeholder}

            GROUP BY c.id, fc.group_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
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
