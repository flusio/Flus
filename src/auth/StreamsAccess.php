<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class StreamsAccess
{
    public static function canView(?models\User $user, models\Stream $stream): bool
    {
        if ($stream->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->id === $stream->user_id) {
            return true;
        }

        return $stream->sharedWith($user);
    }

    public static function canCreateViews(?models\User $user, models\Stream $stream): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->id === $stream->user_id) {
            return true;
        }

        return $stream->sharedWith($user);
    }

    public static function canUpdate(?models\User $user, models\Stream $stream): bool
    {
        return $user && $user->id === $stream->user_id;
    }

    public static function canDelete(?models\User $user, models\Stream $stream): bool
    {
        return $user && $user->id === $stream->user_id;
    }
}
