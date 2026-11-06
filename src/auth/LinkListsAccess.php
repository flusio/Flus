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

        if ($link_list instanceof models\Journal) {
            return $user->isOwningJournal($link_list);
        }

        if ($link_list instanceof models\Collection) {
            return $user->isOwningCollection($link_list) || $link_list->sharedWith($user);
        }

        return false;
    }

    /**
     * Return the SQL condition limiting the lists to those that the given user
     * can view, along with its parameters.
     *
     * The condition applies to the collections table aliased as $alias.
     *
     * It must be kept in sync with canView().
     *
     * @param literal-string $alias
     *
     * @return array{literal-string, array<string, mixed>}
     */
    public static function viewQuery(?models\User $user, string $alias): array
    {
        if (!$user) {
            return ["({$alias}.is_public = true)", []];
        }

        $sql = <<<SQL
            (
                {$alias}.is_public = true
                OR {$alias}.user_id = :access_user_id
                OR EXISTS (
                    SELECT 1 FROM collection_shares access_cs
                    WHERE access_cs.user_id = :access_user_id
                    AND access_cs.collection_id = {$alias}.id
                )
            )
        SQL;

        return [$sql, [':access_user_id' => $user->id]];
    }

    public static function canViewHiddenLinks(?models\User $user, models\LinkList $link_list): bool
    {
        if (!$user) {
            return false;
        }

        if ($link_list instanceof models\Journal) {
            return $user->isOwningJournal($link_list);
        }

        if ($link_list instanceof models\Collection) {
            return $user->isOwningCollection($link_list) || $link_list->sharedWith($user);
        }

        return false;
    }

    public static function canUpdate(?models\User $user, models\LinkList $link_list): bool
    {
        if (!$user || !($link_list instanceof models\Collection)) {
            return false;
        }

        if ($user->isOwningCollection($link_list)) {
            return true;
        }

        return $link_list->sharedWith($user, 'write');
    }

    public static function canUpdateGroup(?models\User $user, models\LinkList $link_list): bool
    {
        return (
            $user &&
            $link_list instanceof models\Collection &&
            $user->isOwningCollection($link_list)
        );
    }

    public static function canAddLinks(?models\User $user, models\LinkList $link_list): bool
    {
        if (!$user || !($link_list instanceof models\Collection)) {
            return false;
        }

        if ($user->isOwningCollection($link_list)) {
            return true;
        }

        return $link_list->sharedWith($user, 'write');
    }

    public static function canDelete(?models\User $user, models\LinkList $link_list): bool
    {
        return (
            $user &&
            $link_list instanceof models\Collection &&
            $user->isOwningCollection($link_list)
        );
    }
}
