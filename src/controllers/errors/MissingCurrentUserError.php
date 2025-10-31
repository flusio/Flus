<?php

namespace App\controllers\errors;

/**
 * Raised when a controller needs the user to be logged in. It is catched by
 * the BaseController to redirect to the login page.
 *
 * This error is deprecated and replaced by \App\auth\MissingCurrentUserError.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MissingCurrentUserError extends \RuntimeException
{
    public function __construct(
        public string $redirect_after_login
    ) {
        parent::__construct();
    }
}
