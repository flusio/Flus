<?php

namespace App\auth;

use App\models;

/**
 * Raised when a user cannot perform an action on a given subject. It is
 * catched by the BaseController to show an error page.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AccessDeniedError extends \RuntimeException
{
    public function __construct(?models\User $user, string $action, object $subject)
    {
        if ($user) {
            $message = "User {$user->id}";
        } else {
            $message = "Visitor";
        }

        $subject_class = $subject::class;
        $message .= " cannot perform action '{$action}' on {$subject_class}";

        if (is_callable([$subject, 'primaryKeyColumn'])) {
            $pk_column = $subject::primaryKeyColumn();
            $pk_value = $subject->$pk_column;
            $message .= " {$pk_value}";
        }

        parent::__construct($message);
    }
}
