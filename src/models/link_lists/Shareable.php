<?php

namespace App\models\link_lists;

use App\models\CollectionShare;
use App\models\User;
use App\utils\Sorter;
use Minz\Database;

/**
 * Add the ability to share a collection with other users.
 *
 * @phpstan-import-type ShareType from CollectionShare
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Shareable
{
    /**
     * Return the list of users with write access.
     *
     * @return User[]
     */
    public function publishers(): array
    {
        return $this->memoize('publishers', function (): array {
            $owner = $this->owner();
            $shares = $this->shares(['access_type' => 'write']);

            $publishers = array_map(function (CollectionShare $share): User {
                return $share->user();
            }, $shares);

            array_unshift($publishers, $owner);

            return $publishers;
        });
    }

    /**
     * Set the publishers of the collection without querying the database.
     *
     * @see Preloader
     *
     * @param User[] $publishers
     */
    public function preloadPublishers(array $publishers): void
    {
        $this->memoizeValue('publishers', $publishers);
    }

    /**
     * Return the CollectionShares attached to the current collection
     *
     * @see CollectionShare::listComputedByCollectionId
     *
     * @param array{
     *     'access_type'?: ShareType|'any',
     * } $options
     *
     * @return CollectionShare[]
     */
    public function shares(array $options = []): array
    {
        $collection_shares = CollectionShare::listComputedByCollectionId(
            $this->id,
            ['username'],
            $options,
        );
        return Sorter::localeSort($collection_shares, 'username');
    }

    /**
     * Share the collection with the given user.
     *
     * @param ShareType $access_type
     */
    public function shareWith(User $user, string $access_type): void
    {
        $collection_share = new CollectionShare($user->id, $this->id, $access_type);
        $collection_share->save();

        $this->unmemoize('publishers');
    }

    /**
     * Unshare the collection with the given user.
     */
    public function unshareWith(User $user): void
    {
        CollectionShare::deleteBy([
            'collection_id' => $this->id,
            'user_id' => $user->id,
        ]);

        $this->unmemoize('publishers');
    }

    /**
     * Return whether the collections is shared with the given user.
     *
     * If $access_type is 'any' or 'read', the method returns true just if a
     * collection_share exists for this collection and user.
     *
     * If $access_type is 'write', the method will check that the collection
     * share has a 'write' type.
     *
     * @param ShareType|'any' $access_type
     */
    public function sharedWith(User $user, string $access_type = 'any'): bool
    {
        $existing_collection_share = CollectionShare::findBy([
            'collection_id' => $this->id,
            'user_id' => $user->id,
        ]);

        if (!$existing_collection_share) {
            return false;
        }

        return (
            $access_type === 'any' ||
            $access_type === 'read' ||
            $access_type === $existing_collection_share->type
        );
    }

    /**
     * Return the collections shared to the given user with its computed properties.
     *
     * @param User $user
     *     The user with who the collections are shared.
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
     * - access_type (default is 'any'), indicates with which access the
     *   collections must have been shared.
     *
     * @return self[]
     */
    public static function listComputedSharedToUser(
        User $user,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'access_type' => 'any',
        ];
        $options = array_merge($default_options, $options);

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.id) AS number_links';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = c.id
            SQL;
            $group_by_clause = 'GROUP BY c.id';
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
                c.*
                {$number_links_clause}
            FROM collection_shares cs, collections c

            {$join_clause}

            WHERE cs.collection_id = c.id
            AND cs.user_id = :user_id
            AND c.type = 'collection'

            {$access_type_clause}

            {$group_by_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * List collections which are writable by the given user, containing a link
     * not owned by the given user and with the given URL.
     *
     * @return self[]
     */
    public static function listWritableContainingNotOwnedLinkWithUrl(User $user, string $url_hash): array
    {
        $sql = <<<'SQL'
            SELECT c.*
            FROM links_to_collections lc, links l, collections c

            WHERE lc.collection_id = c.id
            AND lc.link_id = l.id
            AND c.type = 'collection'

            AND l.user_id IS DISTINCT FROM :user_id
            AND l.url_hash = :url_hash

            AND (
                c.user_id = :user_id OR EXISTS (
                    SELECT 1
                    FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                    AND cs.type = 'write'
                )
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
            ':url_hash' => $url_hash,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
