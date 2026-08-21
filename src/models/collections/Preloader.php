<?php

namespace App\models\collections;

use App\models\Collection;
use App\models\CollectionShare;
use App\models\FollowedCollection;
use App\models\StreamToFollow;
use App\models\User;
use App\utils\Sorter;

/**
 * Load in batch the data that a list of collections would otherwise load one
 * by one.
 *
 * The loaded values are pushed in the memoizer cache of the collections, so
 * that the corresponding methods return them without querying the database
 * again. A list of collections can then be rendered with a constant number of
 * queries.
 *
 *     Preloader::for($sources)
 *         ->publishers()
 *         ->countStreamsFor($user)
 *         ->followsFor($user);
 *
 * The methods taking a user accept a null one, so that they can be chained
 * without condition when the current user is optional.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Preloader
{
    /**
     * @param Collection[] $collections
     */
    private function __construct(
        private array $collections,
    ) {
    }

    /**
     * @param Collection[] $collections
     */
    public static function for(array $collections): self
    {
        return new self($collections);
    }

    /**
     * Preload the publishers of the collections, that is their owner followed
     * by the users having a write access to them.
     */
    public function publishers(): self
    {
        $writer_ids_by_collections = CollectionShare::listWriterIdsByCollections($this->collections);

        $user_ids = array_column($this->collections, 'user_id');

        foreach ($writer_ids_by_collections as $writer_ids) {
            $user_ids = array_merge($user_ids, $writer_ids);
        }

        $user_ids = array_filter($user_ids);
        $user_ids = array_unique($user_ids);
        $user_ids = array_values($user_ids);

        $users_by_ids = [];

        if ($user_ids) {
            $users = User::listBy(['id' => $user_ids]);
            $users_by_ids = array_column($users, null, 'id');
        }

        foreach ($this->collections as $collection) {
            $owner = null;

            if ($collection->user_id) {
                $owner = $users_by_ids[$collection->user_id] ?? null;
            }

            $collection->preloadOwner($owner);

            $writers = [];

            foreach ($writer_ids_by_collections[$collection->id] ?? [] as $writer_id) {
                if (isset($users_by_ids[$writer_id])) {
                    $writers[] = $users_by_ids[$writer_id];
                }
            }

            $writers = Sorter::localeSort($writers, 'username');

            if ($owner) {
                array_unshift($writers, $owner);
            }

            $collection->preloadPublishers($writers);
        }

        return $this;
    }

    /**
     * Preload the number of streams of the given user in which the collections
     * are sources.
     */
    public function countStreamsFor(?User $user): self
    {
        if (!$user) {
            return $this;
        }

        $counts = StreamToFollow::countByUserAndSources($user, $this->collections);

        foreach ($this->collections as $collection) {
            $collection->preloadCountStreamsByUser($user, $counts[$collection->id] ?? 0);
        }

        return $this;
    }

    /**
     * Preload the follows of the given user on the collections.
     */
    public function followsFor(?User $user): self
    {
        if (!$user) {
            return $this;
        }

        $follows = FollowedCollection::listByUserAndCollections($user, $this->collections);

        foreach ($this->collections as $collection) {
            $collection->preloadFollowByUser($user, $follows[$collection->id] ?? null);
        }

        return $this;
    }
}
