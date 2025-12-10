<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksAccess
{
    public static function canView(?models\User $user, models\Link $link): bool
    {
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

    public static function canUpdate(?models\User $user, models\Link $link): bool
    {
        return $user && $user->id === $link->user_id;
    }

    public static function canDelete(?models\User $user, models\Link $link): bool
    {
        return $user && $user->id === $link->user_id;
    }

    public static function canShareOnMastodon(?models\User $user, models\Link $link): bool
    {
        return $user && $user->isMastodonEnabled() && self::canView($user, $link);
    }
}
