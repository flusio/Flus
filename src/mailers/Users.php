<?php

namespace flusio\mailers;

class Users extends \Minz\Mailer
{
    public function sendAccountValidationEmail($user, $token)
    {
        $brand = \Minz\Configuration::$application['brand'];
        $subject = vsprintf(_('[%s] Confirm your account'), $brand);
        $this->setBody(
            'mailers/users/account_validation_email.phtml',
            'mailers/users/account_validation_email.txt',
            [
                'username' => $user->username,
                'token' => $token->token,
            ]
        );
        return $this->send($user->email, $subject);
    }
}
