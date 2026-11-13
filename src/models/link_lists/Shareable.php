<?php

namespace App\models\link_lists;

use App\models\CollectionShare;
use App\models\User;
use App\utils\ArrayHelper;
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

            $publishers = [];

            foreach ($this->shares() as $share) {
                if ($share->type === 'write') {
                    $publishers[] = $share->user();
                }
            }

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
     * Return the shares of the collection, sorted by username.
     *
     * @return CollectionShare[]
     */
    public function shares(): array
    {
        return $this->memoize('shares_sorted', fn (): array => Sorter::localeSort(
            $this->unsortedShares(),
            fn (CollectionShare $share): string => $share->user()->username,
        ));
    }

    /**
     * Return the shares of the collection, in no particular order.
     *
     * Unlike shares(), it doesn't load the users to sort the shares. It is
     * better to use this method when only shares matter (e.g. to check an access).
     *
     * @return CollectionShare[]
     */
    private function unsortedShares(): array
    {
        if ($this->isMemoized('shares_sorted')) {
            return $this->shares();
        }

        return $this->memoize('shares_unsorted', fn (): array => CollectionShare::listBy([
            'collection_id' => $this->id,
        ]));
    }

    /**
     * Set the shares of the collection without querying the database.
     *
     * The shares must be sorted by username.
     *
     * @see Preloader
     *
     * @param CollectionShare[] $shares
     */
    public function preloadShares(array $shares): void
    {
        $this->memoizeValue('shares_sorted', $shares);
    }

    /**
     * Share the collection with the given user.
     *
     * @param ShareType $access_type
     */
    public function shareWith(User $user, string $access_type): void
    {
        $collection_share = new CollectionShare($user, $this, $access_type);
        $collection_share->save();

        $this->unmemoizePrefixed('shares_');
        $this->unmemoizePrefixed('count_links_');
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

        $this->unmemoizePrefixed('shares_');
        $this->unmemoizePrefixed('count_links_');
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
        $existing_collection_share = ArrayHelper::find(
            $this->unsortedShares(),
            fn (CollectionShare $collection_share): bool => $collection_share->user_id === $user->id,
        );

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
     * Return the collections shared to the given user.
     *
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
    public static function listSharedToUser(User $user, array $options = []): array
    {
        $default_options = [
            'access_type' => 'any',
        ];
        $options = array_merge($default_options, $options);

        // we don't need the clause if access_type is 'any' (i.e. the type
        // doesn't matter) or 'read' (i.e. read access is included in write
        // access)
        $access_type_clause = '';
        if ($options['access_type'] === 'write') {
            $access_type_clause = "AND cs.type = 'write'";
        }

        $sql = <<<SQL
            SELECT c.*
            FROM collection_shares cs, collections c

            WHERE cs.collection_id = c.id
            AND cs.user_id = :user_id
            AND c.type = 'collection'

            {$access_type_clause}
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
