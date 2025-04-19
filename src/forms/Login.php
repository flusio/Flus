<?php

namespace App\forms;

use App\models;
use Minz\Form;

/**
 * @extends BaseForm<\stdClass>
 */
class Login extends BaseForm
{
    #[Form\Field]
    public string $email = '';

    #[Form\Field]
    public string $password = '';

    #[Form\Check]
    public function checkEmailFormat(): void
    {
        $email = \Minz\Email::sanitize($this->email);
        if (!\Minz\Email::validate($email)) {
            $this->addError('email', _('The address email is invalid.'));
        }
    }

    #[Form\Check]
    public function checkCredentials(): void
    {
        try {
            $user = $this->getUser();
        } catch (\RuntimeException $e) {
            $this->addError('email', _('We can’t find any account with this email address.'));
            return;
        }

        if ($user->isSupportUser()) {
            $this->addError('@global', _('What are you trying to do? You can’t login to the support account.'));
            return;
        }

        if (!$user->verifyPassword($this->password)) {
            $this->addError('password', _('The password is incorrect.'));
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
