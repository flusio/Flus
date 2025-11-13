<?php

namespace App\forms;

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
        $user = $this->user();
        if (!$user->verifyPassword($this->password)) {
            $this->addError(
                'password',
                'incorrect_password',
                _('The password is incorrect.'),
            );
        }
    }

    public function user(): models\User
    {
        $user = $this->options->get('user');

        if (!($user instanceof models\User)) {
            throw new \LogicException('User must be passed as an option of the form.');
        }

        return $user;
    }
}
