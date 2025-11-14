<?php

namespace App\forms\security;

use App\forms\BaseForm;
use App\forms\traits;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ConfirmPassword extends BaseForm
{
    use traits\PasswordChecker;
    use traits\Redirectable;
}
