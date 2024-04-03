<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class GroupsAccess
{
    public static function canUpdate(?models\User $user, models\Group $group): bool
    {
        return $user && $user->id === $group->user_id;
    }

    public static function canDelete(?models\User $user, models\Group $group): bool
    {
        return $user && $user->id === $group->user_id;
    }
}
