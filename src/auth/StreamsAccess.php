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
        return $user && $user->id === $stream->user_id;
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
