<?php

namespace flusio\models\dao;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionShare extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\CollectionShare::PROPERTIES);
        parent::__construct('collection_shares', 'id', $properties);
    }

    /**
     * Return CollectionShares of the given collection with its computed properties.
     *
     * @param string $collection_id
     *     The collection id the links must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     *
     * @return array
     */
    public function listComputedByCollectionId($collection_id, $selected_computed_props)
    {
        $parameters = [
            ':collection_id' => $collection_id,
        ];

        $username_clause = '';
        $join_clause = '';
        if (in_array('username', $selected_computed_props)) {
            $username_clause = ', u.username';
            $join_clause = 'INNER JOIN users u ON u.id = cs.user_id';
        }

        $sql = <<<SQL
            SELECT
                cs.*
                {$username_clause}
            FROM collection_shares cs

            {$join_clause}

            WHERE cs.collection_id = :collection_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    /**
     * Return whether the link is shared with the given user (i.e. it is
     * attached to a shared collection).
     *
     * @param string $user_id
     * @param string $link_id
     *
     * @return boolean
     */
    public function existsForUserIdAndLinkId($user_id, $link_id)
    {
        $sql = <<<'SQL'
            SELECT TRUE
            FROM collection_shares cs, links_to_collections lc

            WHERE cs.collection_id = lc.collection_id
            AND cs.user_id = :user_id
            AND lc.link_id = :link_id
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':link_id' => $link_id,
        ]);
        return $statement->fetchColumn();
    }
}
