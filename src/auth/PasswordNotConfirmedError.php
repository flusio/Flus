<?php

namespace App\auth;

/**
 * Raised when the user needs to confirm the password. It is catched by the
 * BaseController to redirect to the password confirmation page.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class PasswordNotConfirmedError extends \RuntimeException
{
}
