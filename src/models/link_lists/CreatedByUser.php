<?php

namespace App\models\link_lists;

use App\models\User;
use Minz\Database;

/**
 * Add the relation between a list and the user who created it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait CreatedByUser
{
    #[Database\Column]
    public string $user_id;

    /**
     * Return the user who created the list.
     */
    public function user(): User
    {
        return $this->memoize('user', fn(): User => User::require($this->user_id));
    }

    /**
     * Set the user who created the list.
     */
    public function setUser(User $user): void
    {
        $this->user_id = $user->id;
        $this->memoizeValue('user', $user);
    }

    /**
     * Set the user who created the list without querying the database.
     *
     * @see \App\utils\OriginFormatter::preloadOrigins
     */
    public function preloadUser(User $user): void
    {
        $this->memoizeValue('user', $user);
    }
}
