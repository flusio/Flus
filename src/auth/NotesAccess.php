<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NotesAccess
{
    public static function canUpdate(?models\User $user, models\Note $note): bool
    {
        return $user && $user->id === $note->user_id;
    }

    public static function canDelete(?models\User $user, models\Note $note): bool
    {
        return $user && $user->id === $note->user_id;
    }
}
