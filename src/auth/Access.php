<?php

namespace App\auth;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Access
{
    /**
     * Return true if a user can do a specific action on the given subject, or
     * false otherwise.
     *
     * The user can be null in case of a disconnected user.
     *
     * This method checks the permission using on of the *Access class
     * (depending on the type of the subject). It calls the `can[Action]`
     * method on the corresponding Access class.
     *
     * @throws \InvalidArgumentException
     *     If the subject type, or the action are not supported.
     */
    public static function can(?models\User $user, string $action, object $subject): bool
    {
        $subject_class = $subject::class;

        if ($subject instanceof models\Collection) {
            $access_class = CollectionsAccess::class;
        } elseif ($subject instanceof models\Group) {
            $access_class = GroupsAccess::class;
        } elseif ($subject instanceof models\Link) {
            $access_class = LinksAccess::class;
        } elseif ($subject instanceof models\Note) {
            $access_class = NotesAccess::class;
        } elseif ($subject instanceof models\Importation) {
            $access_class = ImportationsAccess::class;
        } else {
            throw new \InvalidArgumentException("{$subject_class} subject is not supported");
        }

        $method = ucfirst($action);
        $method = "can{$method}";

        if (!is_callable([$access_class, $method])) {
            throw new \InvalidArgumentException("{$method} method doesn't exist in {$access_class}");
        }

        return $access_class::$method($user, $subject);
    }

    /**
     * Require a user to be able to do a specific action on the given subject.
     *
     * @throws AccessDeniedError
     *     If the user is not authorized to do the action on the subject.
     * @throws \InvalidArgumentException
     *     If the subject type, or the action are not supported.
     */
    public static function require(?models\User $user, string $action, object $subject): void
    {
        $can = self::can($user, $action, $subject);

        if (!$can) {
            throw new AccessDeniedError($user, $action, $subject);
        }
    }
}
