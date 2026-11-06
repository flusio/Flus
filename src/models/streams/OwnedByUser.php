<?php

namespace App\models\streams;

use App\models\User;
use Minz\Database;

/**
 * Add the relation between a stream and the user who owns it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait OwnedByUser
{
    #[Database\Column]
    public string $user_id;

    /**
     * Return the owner of the stream.
     */
    public function owner(): User
    {
        return $this->memoize('owner', function (): User {
            return User::require($this->user_id);
        });
    }

    /**
     * Set the owner of the stream.
     */
    public function setOwner(User $user): void
    {
        $this->user_id = $user->id;
        $this->memoizeValue('owner', $user);
    }

    /**
     * List the streams owned by the given user.
     *
     * By default, the private streams are listed as well: pass the is_private
     * option to false to only list the public ones.
     *
     * @param array{
     *     'is_private'?: bool,
     * } $options
     *
     * @return self[]
     */
    public static function listByUser(User $user, array $options = []): array
    {
        $is_private = $options['is_private'] ?? true;

        $criteria = ['user_id' => $user->id];

        if (!$is_private) {
            $criteria['is_public'] = true;
        }

        return self::listBy($criteria);
    }
}
