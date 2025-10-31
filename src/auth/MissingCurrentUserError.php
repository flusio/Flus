<?php

namespace App\auth;

/**
 * Raised when the user needs to be logged in. It is catched by the
 * BaseController to redirect to the login page.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MissingCurrentUserError extends \RuntimeException
{
}
