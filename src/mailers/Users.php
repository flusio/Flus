<?php

namespace flusio\mailers;

use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Users extends \Minz\Mailer
{
    /**
     * Send an email to given user to validate its account.
     *
     * @param string $user_id
     */
    public function sendAccountValidationEmail($user_id)
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send validation email to user {$user_id} (not found)");
            return;
        }

        if (!$user->validation_token) {
            \Minz\Log::warning("Can’t send validation email to user {$user_id} (no token)");
            return;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $brand = \Minz\Configuration::$application['brand'];
        $subject = vsprintf(_('[%s] Confirm your account'), $brand);
        $this->setBody(
            'mailers/users/account_validation_email.phtml',
            'mailers/users/account_validation_email.txt',
            [
                'username' => $user->username,
                'token' => $user->validation_token,
            ]
        );
        return $this->send($user->email, $subject);
    }

    /**
     * Send an email to the given user to reset its password.
     *
     * @param string $user_id
     */
    public function sendResetPasswordEmail($user_id)
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send reset email to user {$user_id} (not found)");
            return;
        }

        if (!$user->reset_token) {
            \Minz\Log::warning("Can’t send reset email to user {$user_id} (no token)");
            return;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $brand = \Minz\Configuration::$application['brand'];
        $subject = vsprintf(_('[%s] Reset your password'), $brand);
        $this->setBody(
            'mailers/users/reset_password_email.phtml',
            'mailers/users/reset_password_email.txt',
            [
                'brand' => $brand,
                'username' => $user->username,
                'token' => $user->reset_token,
            ]
        );
        return $this->send($user->email, $subject);
    }
}
