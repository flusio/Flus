<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionsAccess
{
    public static function canView(?models\User $user, models\Collection $collection): bool
    {
        if ($collection->is_public) {
            return true;
        }

        if (!$user) {
            return false;
        }

        if ($user->id === $collection->user_id) {
            return true;
        }

        return $collection->sharedWith($user);
    }

    public static function canViewHiddenLinks(?models\User $user, models\Collection $collection): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->id === $collection->user_id) {
            return true;
        }

        return $collection->sharedWith($user);
    }

    public static function canUpdate(?models\User $user, models\Collection $collection): bool
    {
        if (!$user) {
            return false;
        }

        if ($collection->type !== 'collection') {
            return false;
        }

        if ($user->id === $collection->user_id) {
            return true;
        }

        return $collection->sharedWith($user, 'write');
    }

    public static function canUpdateGroup(?models\User $user, models\Collection $collection): bool
    {
        return (
            $user &&
            $collection->type === 'collection' &&
            $user->id === $collection->user_id
        );
    }

    public static function canAddLinks(?models\User $user, models\Collection $collection): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->id === $collection->user_id) {
            return true;
        }

        return $collection->sharedWith($user, 'write');
    }

    public static function canDelete(?models\User $user, models\Collection $collection): bool
    {
        return (
            $user &&
            $user->id === $collection->user_id &&
            $collection->type === 'collection'
        );
    }
}
