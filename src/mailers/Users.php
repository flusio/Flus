<?php

namespace flusio\mailers;

class Users extends \Minz\Mailer
{
    public function sendRegistrationValidationEmail($user, $token)
    {
        $brand = \Minz\Configuration::$application['brand'];
        $subject = vsprintf(_('[%s] Confirm your registration'), $brand);
        $this->setBody(
            'mailers/users/registration_validation_email.phtml',
            'mailers/users/registration_validation_email.txt',
            [
                'username' => $user->username,
                'token' => $token->token,
            ]
        );
        return $this->send($user->email, $subject);
    }
}
