<?php

namespace App\models\streams;

use App\models\StreamShare;
use App\models\User;
use App\utils;

/**
 * Add the ability to share a stream with other users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Shareable
{
    /**
     * Return the shares of the stream, sorted by username.
     *
     * @return StreamShare[]
     */
    public function shares(): array
    {
        return $this->memoize('shares', function (): array {
            $stream_shares = StreamShare::listBy([
                'stream_id' => $this->id,
            ]);

            return utils\Sorter::localeSort(
                $stream_shares,
                fn (StreamShare $share): string => $share->user()->username,
            );
        });
    }

    /**
     * Set the shares of the stream without querying the database.
     *
     * @see Preloader
     *
     * @param StreamShare[] $shares
     */
    public function preloadShares(array $shares): void
    {
        $this->memoizeValue('shares', $shares);
    }

    /**
     * Share the stream with the given user.
     */
    public function shareWith(User $user): void
    {
        $stream_share = new StreamShare($user, $this);
        $stream_share->save();

        $this->unmemoize('shares');
        $this->unmemoizePrefixed('shared_with_');
    }

    /**
     * Unshare the stream with the given user.
     */
    public function unshareWith(User $user): void
    {
        StreamShare::deleteBy([
            'stream_id' => $this->id,
            'user_id' => $user->id,
        ]);

        $this->unmemoize('shares');
        $this->unmemoizePrefixed('shared_with_');
    }

    /**
     * Return whether the stream is shared with the given user.
     */
    public function sharedWith(User $user): bool
    {
        return $this->memoize(['shared_with', $user], fn(): bool => StreamShare::existsBy([
            'stream_id' => $this->id,
            'user_id' => $user->id,
        ]));
    }

    /**
     * List the streams shared with the given user.
     *
     * @return self[]
     */
    public static function listSharedToUser(User $user): array
    {
        $sql = <<<SQL
            SELECT s.*
            FROM streams s, stream_shares ss

            WHERE s.id = ss.stream_id
            AND ss.user_id = :user_id
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            'user_id' => $user->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
