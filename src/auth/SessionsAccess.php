<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SessionsAccess
{
    public static function canDelete(?models\User $user, models\Session $session): bool
    {
        return (
            $user &&
            $user->id === $session->user_id
        );
    }
}
