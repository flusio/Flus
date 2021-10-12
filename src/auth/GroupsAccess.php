<?php

namespace flusio\auth;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class GroupsAccess
{
    public static function canView($user, $group)
    {
        return $user && $group && $user->id === $group->user_id;
    }

    public static function canUpdate($user, $group)
    {
        return $user && $group && $user->id === $group->user_id;
    }

    public static function canDelete($user, $group)
    {
        return $user && $group && $user->id === $group->user_id;
    }
}
