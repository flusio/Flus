<?php

namespace flusio\auth;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksAccess
{
    public static function canView($user, $link)
    {
        if (!$link) {
            return false;
        }

        if (!$link->is_hidden) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->id === $link->user_id) {
            return true;
        }

        return $link->sharedWith($user);
    }

    public static function canUpdate($user, $link)
    {
        return $user && $link && $user->id === $link->user_id;
    }

    public static function canDelete($user, $link)
    {
        return $user && $link && $user->id === $link->user_id;
    }
}
