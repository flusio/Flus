<?php

namespace App\forms\api;

use App\models;
use Minz\Form;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Session extends Form
{
    #[Form\Field]
    #[Validable\Presence(message: 'The address email is required.')]
    public string $email = '';

    #[Form\Field]
    #[Validable\Presence(message: 'The password is required.')]
    public string $password = '';

    #[Form\Field]
    #[Validable\Presence(message: 'The app name is required.')]
    public string $app_name = '';

    #[Validable\Check]
    public function checkCredentials(): void
    {
        if ($this->isInvalid('email') || $this->isInvalid('password')) {
            // Don't check credentials if email or password are in error.
            return;
        }

        try {
            $user = $this->getUser();
        } catch (\RuntimeException $e) {
            $this->addError('@base', 'invalid_credentials', 'The credentials are invalid.');
            return;
        }

        if (
            $user->isSupportUser() ||
            !$user->verifyPassword($this->password)
        ) {
            $this->addError('@base', 'invalid_credentials', 'The credentials are invalid.');
        }
    }

    public function getUser(): models\User
    {
        $email = \Minz\Email::sanitize($this->email);
        $user = models\User::findBy(['email' => $email]);

        if (!$user) {
            throw new \RuntimeException("Unknown user {$email}");
        }

        return $user;
    }
}
