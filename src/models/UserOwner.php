<?php

namespace App\models;

use Minz\Database;

/**
 * The owner corresponding to a user.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UserOwner extends Owner
{
    #[Database\Column]
    public string $user_id;

    public function __construct(User $user)
    {
        $this->id = \Minz\Random::timebased();
        $this->type = 'user';
        $this->setUser($user);
    }

    /**
     * Return the user corresponding to the owner.
     */
    public function user(): User
    {
        return $this->memoize('user', fn(): User => User::require($this->user_id));
    }

    /**
     * Set the user corresponding to the owner.
     */
    public function setUser(User $user): void
    {
        $this->user_id = $user->id;
        $this->memoizeValue('user', $user);
    }

    /**
     * Return the owner of the given user and create it if it doesn't exist yet.
     *
     * You should never call it directly: use the `User::owner()` method instead.
     */
    public static function findOrCreateFor(User $user): self
    {
        return self::findOrInsert(['user_id' => $user->id], new self($user));
    }
}
