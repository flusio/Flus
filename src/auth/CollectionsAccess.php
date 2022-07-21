<?php

namespace flusio\auth;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionsAccess
{
    public static function canView($user, $collection)
    {
        if (!$collection) {
            return false;
        }

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

    public static function canUpdate($user, $collection)
    {
        return (
            $user && $collection &&
            $user->id === $collection->user_id &&
            $collection->type === 'collection'
        );
    }

    public static function canUpdateRead($user, $collection)
    {
        return (
            $user && $collection &&
            $user->id === $collection->user_id &&
            ($collection->type === 'collection' || $collection->type === 'news')
        );
    }

    public static function canDelete($user, $collection)
    {
        return (
            $user && $collection &&
            $user->id === $collection->user_id &&
            $collection->type === 'collection'
        );
    }
}
