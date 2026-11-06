<?php

namespace App\models\users;

use App\models\Follow;
use App\models\Source;
use App\utils;

/**
 * Add methods to manage the sources followed by the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasFollows
{
    /**
     * Return the follow of the user on the given source, or null if the user
     * doesn't follow it.
     */
    public function findFollow(Source $source): ?Follow
    {
        return $this->follows()[$source->id] ?? null;
    }

    /**
     * Return true if the current user is following the given source.
     */
    public function isFollowing(Source $source): bool
    {
        return $this->findFollow($source) !== null;
    }

    /**
     * Return the follows of the user, indexed by source ids.
     *
     * @return array<string, Follow>
     */
    public function follows(): array
    {
        return $this->memoize('follows', function (): array {
            $follows = Follow::listBy(['user_id' => $this->id]);
            return array_column($follows, null, 'collection_id');
        });
    }

    /**
     * Make the current user following the given source.
     *
     * Be careful to check isFollowing() is returning false before calling this
     * method.
     */
    public function follow(Source $source, string $time_filter = 'normal'): Follow
    {
        $follow = new Follow($this, $source);
        $follow->time_filter = $time_filter;
        $follow->save();

        $this->unmemoize('follows');

        return $follow;
    }

    /**
     * Make the current user unfollowing the given source.
     */
    public function unfollow(Source $source): void
    {
        Follow::deleteBy([
            'user_id' => $this->id,
            'collection_id' => $source->id,
        ]);

        $this->unmemoize('follows');
    }

    /**
     * Make the current user unfollowing the given sources.
     *
     * @param Source[] $sources
     */
    public function unfollowAll(array $sources): void
    {
        if (!$sources) {
            return;
        }

        Follow::deleteBy([
            'user_id' => $this->id,
            'collection_id' => array_column($sources, 'id'),
        ]);

        $this->unmemoize('follows');
    }

    /**
     * Return the sources followed by the user.
     *
     * The sources are sorted by name.
     *
     * @see Source::listFollowedByUser
     *
     * @return Source[]
     */
    public function followedSources(): array
    {
        $sources = Source::listFollowedByUser($this);

        return utils\Sorter::localeSort(
            $sources,
            function (Source $source): string {
                return $source->nameByUser($this);
            },
        );
    }
}
