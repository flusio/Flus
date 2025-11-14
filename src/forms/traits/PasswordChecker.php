<?php

namespace App\forms\traits;

use App\models;
use Minz\Form;
use Minz\Request;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait PasswordChecker
{
    #[Form\Field]
    public string $password = '';

    #[Validable\Check]
    public function checkPassword(): void
    {
        $user = $this->optionAs('user', models\User::class);
        if (!$user->verifyPassword($this->password)) {
            $this->addError(
                'password',
                'incorrect_password',
                _('The password is incorrect.'),
            );
        }
    }
}
