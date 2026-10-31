<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkListsAccess
{
    public static function canView(?models\User $user, models\LinkList $link_list): bool
    {
        if ($link_list instanceof models\Source && $link_list->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->isOwning($link_list)) {
            return true;
        }

        return $link_list instanceof models\Collection && $link_list->sharedWith($user);
    }

    public static function canViewHiddenLinks(?models\User $user, models\LinkList $link_list): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isOwning($link_list)) {
            return true;
        }

        return $link_list instanceof models\Collection && $link_list->sharedWith($user);
    }

    public static function canUpdate(?models\User $user, models\LinkList $link_list): bool
    {
        if (!$user || !($link_list instanceof models\Collection)) {
            return false;
        }

        if ($user->isOwning($link_list)) {
            return true;
        }

        return $link_list->sharedWith($user, 'write');
    }

    public static function canUpdateGroup(?models\User $user, models\LinkList $link_list): bool
    {
        return (
            $user &&
            $link_list instanceof models\Collection &&
            $user->isOwning($link_list)
        );
    }

    public static function canAddLinks(?models\User $user, models\LinkList $link_list): bool
    {
        if (!$user || !($link_list instanceof models\Collection)) {
            return false;
        }

        if ($user->isOwning($link_list)) {
            return true;
        }

        return $link_list->sharedWith($user, 'write');
    }

    public static function canDelete(?models\User $user, models\LinkList $link_list): bool
    {
        return (
            $user &&
            $link_list instanceof models\Collection &&
            $user->isOwning($link_list)
        );
    }
}
