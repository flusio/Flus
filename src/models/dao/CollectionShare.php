<?php

namespace App\models\dao;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait CollectionShare
{
    /**
     * Return CollectionShares of the given collection with its computed properties.
     *
     * @param string $collection_id
     *     The collection id the links must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'access_type'?: 'any'|'read'|'write',
     * } $options
     *
     * Description of the options:
     *
     * - access_type (default is 'any'): indicates with which access the
     *   collections must have been shared.
     *
     * @return self[]
     */
    public static function listComputedByCollectionId(
        string $collection_id,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'access_type' => 'any',
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':collection_id' => $collection_id,
        ];

        $username_clause = '';
        $join_clause = '';
        if (in_array('username', $selected_computed_props)) {
            $username_clause = ', u.username';
            $join_clause = 'INNER JOIN users u ON u.id = cs.user_id';
        }

        // we don't need the clause if access_type is 'any' (i.e. the type
        // doesn't matter) or 'read' (i.e. read access is included in write
        // access)
        $access_type_clause = '';
        if ($options['access_type'] === 'write') {
            $access_type_clause = "AND cs.type = 'write'";
        }

        $sql = <<<SQL
            SELECT
                cs.*
                {$username_clause}
            FROM collection_shares cs

            {$join_clause}

            {$access_type_clause}

            WHERE cs.collection_id = :collection_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return whether the link is shared with the given user (i.e. it is
     * attached to a shared collection).
     *
     * @param string $user_id
     * @param string $link_id
     * @param string $access_type
     *
     * @return boolean
     */
    public static function existsForUserIdAndLinkId(
        string $user_id,
        string $link_id,
        string $access_type = 'any',
    ): bool {
        // we don't need the clause if access_type is 'any' (i.e. the type
        // doesn't matter) or 'read' (i.e. read access is included in write
        // access)
        $access_type_clause = '';
        if ($access_type === 'write') {
            $access_type_clause = "AND cs.type = 'write'";
        }

        $sql = <<<SQL
            SELECT EXISTS (
                SELECT 1
                FROM collection_shares cs, links_to_collections lc

                WHERE cs.collection_id = lc.collection_id
                AND cs.user_id = :user_id
                AND lc.link_id = :link_id

                {$access_type_clause}
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user_id,
            ':link_id' => $link_id,
        ]);
        return (bool) $statement->fetchColumn();
    }
}
