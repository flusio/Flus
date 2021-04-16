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

        return $user && $user->id === $link->user_id;
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