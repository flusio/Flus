<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ViewsAccess
{
    public static function canUpdate(?models\User $user, models\View $view): bool
    {
        if (!$user) {
            return false;
        }

        $stream = $view->stream();

        if (!$stream) {
            return false;
        }

        if ($stream->user_id === $user->id) {
            // The owner of the stream can update all the views.
            return true;
        }

        if ($view->is_default) {
            // The default view is shared by everyone viewing it, but only the
            // owner of the stream can update it.
            return false;
        }

        return $user->id === $view->user_id;
    }

    public static function canDelete(?models\User $user, models\View $view): bool
    {
        return self::canUpdate($user, $view);
    }
}
