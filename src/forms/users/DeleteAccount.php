<?php

namespace App\forms\users;

use App\forms\BaseForm;
use App\forms\traits;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class DeleteAccount extends BaseForm
{
    use traits\PasswordChecker;
    use traits\DemoDisabler;
}
