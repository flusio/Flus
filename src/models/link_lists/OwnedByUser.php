<?php

namespace App\models\link_lists;

use App\models\User;
use Minz\Database;

/**
 * Add the relation between a list and the user who owns it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait OwnedByUser
{
    #[Database\Column]
    public string $user_id;

    /**
     * Return the owner of the list.
     */
    public function owner(): User
    {
        return $this->memoize('owner', function (): User {
            return User::require($this->user_id);
        });
    }

    /**
     * Set the owner of the list.
     */
    public function setOwner(User $user): void
    {
        $this->user_id = $user->id;
        $this->memoizeValue('owner', $user);
    }

    /**
     * Set the owner of the list without querying the database.
     *
     * @see \App\utils\OriginFormatter::preloadOrigins
     */
    public function preloadOwner(User $owner): void
    {
        $this->memoizeValue('owner', $owner);
    }
}
