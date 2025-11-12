<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ImportationsAccess
{
    public static function canDelete(?models\User $user, models\Importation $importation): bool
    {
        return $user && $user->id === $importation->user_id;
    }
}
