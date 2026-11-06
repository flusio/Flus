<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'stream_shares')]
class StreamShare
{
    use Database\Recordable;
    use utils\Memoizer;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $stream_id;

    public function __construct(User $user, Stream $stream)
    {
        $this->user_id = $user->id;
        $this->stream_id = $stream->id;
    }

    /**
     * Return the user the stream is shared with.
     */
    public function user(): User
    {
        return $this->memoize('user', function (): User {
            return User::require($this->user_id);
        });
    }

    /**
     * Set the user of the share without querying the database.
     *
     * @see streams\Preloader::shares
     */
    public function preloadUser(User $user): void
    {
        $this->memoizeValue('user', $user);
    }
}
