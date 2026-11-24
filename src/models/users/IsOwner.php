<?php

namespace App\models\users;

use App\models\UserOwner;

/**
 * Make the users owners of contents.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait IsOwner
{
    /**
     * Return the owner of the user and create it if it doesn't exist yet.
     */
    public function owner(): UserOwner
    {
        return $this->memoize('owner', fn(): UserOwner => UserOwner::findOrCreateFor($this));
    }
}
