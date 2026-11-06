<?php

namespace App\models\link_lists;

use App\models\Collection;
use App\models\CollectionShare;
use App\models\Source;
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
 *         ->countStreamsFor($user);
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
     * @param Source[] $sources
     */
    private function __construct(
        private array $sources,
    ) {
    }

    /**
     * @param Source[] $sources
     */
    public static function for(array $sources): self
    {
        return new self($sources);
    }

    /**
     * Preload the publishers of the collections, that is their owner followed
     * by the users having a write access to them.
     */
    public function publishers(): self
    {
        $collections = [];

        foreach ($this->sources as $source) {
            if ($source instanceof Collection) {
                $collections[] = $source;
            }
        }

        $writer_ids_by_collections = CollectionShare::listWriterIdsByCollections($collections);

        $user_ids = array_column($collections, 'user_id');

        foreach ($writer_ids_by_collections as $writer_ids) {
            $user_ids = array_merge($user_ids, $writer_ids);
        }

        $user_ids = array_unique($user_ids);
        $user_ids = array_values($user_ids);

        $users_by_ids = [];

        if ($user_ids) {
            $users = User::listBy(['id' => $user_ids]);
            $users_by_ids = array_column($users, null, 'id');
        }

        foreach ($collections as $collection) {
            $owner = $users_by_ids[$collection->user_id];

            $collection->preloadOwner($owner);

            $writers = [];

            foreach ($writer_ids_by_collections[$collection->id] ?? [] as $writer_id) {
                if (isset($users_by_ids[$writer_id])) {
                    $writers[] = $users_by_ids[$writer_id];
                }
            }

            $writers = Sorter::localeSort($writers, 'username');

            array_unshift($writers, $owner);

            $collection->preloadPublishers($writers);
        }

        return $this;
    }

    /**
     * Preload the shares of the collections, sorted by the username of the
     * users they are shared with, as well as these users.
     */
    public function shares(): self
    {
        $collections = [];

        foreach ($this->sources as $source) {
            if ($source instanceof Collection) {
                $collections[] = $source;
            }
        }

        if (!$collections) {
            return $this;
        }

        $shares = CollectionShare::listBy([
            'collection_id' => array_column($collections, 'id'),
        ]);

        $user_ids = array_column($shares, 'user_id');
        $user_ids = array_unique($user_ids);
        $user_ids = array_values($user_ids);

        $users_by_ids = [];

        if ($user_ids) {
            $users = User::listBy(['id' => $user_ids]);
            $users_by_ids = array_column($users, null, 'id');
        }

        $shares_by_collection_ids = [];

        foreach ($shares as $share) {
            $share->preloadUser($users_by_ids[$share->user_id]);
            $shares_by_collection_ids[$share->collection_id][] = $share;
        }

        foreach ($collections as $collection) {
            $collection_shares = $shares_by_collection_ids[$collection->id] ?? [];

            $collection_shares = Sorter::localeSort(
                $collection_shares,
                fn (CollectionShare $share): string => $share->user()->username,
            );

            $collection->preloadShares($collection_shares);
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

        $counts = StreamToFollow::countByUserAndSources($user, $this->sources);

        foreach ($this->sources as $source) {
            $source->preloadCountStreamsByUser($user, $counts[$source->id] ?? 0);
        }

        return $this;
    }
}
