<?php

namespace App\models\streams;

use App\models\Stream;
use App\models\StreamShare;
use App\models\User;
use App\utils\Sorter;

/**
 * Load in batch the data that a list of streams would otherwise load one by
 * one.
 *
 * The loaded values are pushed in the memoizer cache of the streams, so that
 * the corresponding methods return them without querying the database again. A
 * list of streams can then be rendered with a constant number of queries.
 *
 *     Preloader::for($streams)
 *         ->hasUnreadLinksFor($user);
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
     * @param Stream[] $streams
     */
    private function __construct(
        private array $streams,
    ) {
    }

    /**
     * @param Stream[] $streams
     */
    public static function for(array $streams): self
    {
        return new self($streams);
    }

    /**
     * Preload whether the streams contain unread links for the given user.
     */
    public function hasUnreadLinksFor(?User $user): self
    {
        if (!$user) {
            return $this;
        }

        $streams_with_unread = Stream::listWithUnreadLinks($this->streams, $user);

        foreach ($this->streams as $stream) {
            $has_unread_links = isset($streams_with_unread[$stream->id]);
            $stream->preloadHasUnreadLinks($user, $has_unread_links);
        }

        return $this;
    }

    /**
     * Preload the shares of the streams, sorted by the username of the users
     * they are shared with, as well as these users.
     */
    public function shares(): self
    {
        if (!$this->streams) {
            return $this;
        }

        $shares = StreamShare::listBy([
            'stream_id' => array_column($this->streams, 'id'),
        ]);

        $user_ids = array_column($shares, 'user_id');
        $user_ids = array_unique($user_ids);
        $user_ids = array_values($user_ids);

        $users_by_ids = [];

        if ($user_ids) {
            $users = User::listBy(['id' => $user_ids]);
            $users_by_ids = array_column($users, null, 'id');
        }

        $shares_by_stream_ids = [];

        foreach ($shares as $share) {
            $share->preloadUser($users_by_ids[$share->user_id]);
            $shares_by_stream_ids[$share->stream_id][] = $share;
        }

        foreach ($this->streams as $stream) {
            $stream_shares = $shares_by_stream_ids[$stream->id] ?? [];

            $stream_shares = Sorter::localeSort(
                $stream_shares,
                fn (StreamShare $share): string => $share->user()->username,
            );

            $stream->preloadShares($stream_shares);
        }

        return $this;
    }
}
