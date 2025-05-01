<?php

namespace App;

use App\forms;

/**
 * A temporary class to help to manage CSRF token while upgrading all the forms
 * to the \Minz\Form mechanism.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Csrf
{
    public static function generate(): string
    {
        $csrf = self::csrf();
        return $csrf->get();
    }

    public static function validate(string $token): bool
    {
        $csrf = self::csrf();
        return $csrf->validate($token);
    }

    private static function csrf(): \Minz\Form\CsrfToken
    {
        $base_form = new forms\BaseForm();
        $session_id = $base_form->csrfSessionId();
        return new \Minz\Form\CsrfToken($session_id);
    }
}
