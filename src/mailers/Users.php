<?php

namespace App\mailers;

use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Users extends \Minz\Mailer
{
    /**
     * Send an email to given user to validate its account.
     */
    public function sendAccountValidationEmail(string $user_id): bool
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send validation email to user {$user_id} (not found)");
            return false;
        }

        if (!$user->validation_token) {
            \Minz\Log::warning("Can’t send validation email to user {$user_id} (no token)");
            return false;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $brand = \App\Configuration::$application['brand'];
        $subject = sprintf(_('[%s] Confirm your account'), $brand);
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
     */
    public function sendResetPasswordEmail(string $user_id): bool
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send reset email to user {$user_id} (not found)");
            return false;
        }

        if (!$user->reset_token) {
            \Minz\Log::warning("Can’t send reset email to user {$user_id} (no token)");
            return false;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $brand = \App\Configuration::$application['brand'];
        $subject = sprintf(_('[%s] Reset your password'), $brand);
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

    /**
     * Send an email to the given user to warn about its inactivity and the
     * upcoming deletion of its account.
     */
    public function sendInactivityEmail(string $user_id): bool
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send inactivity email to user {$user_id} (not found)");
            return false;
        }

        if (!$user->isInactive(months: 5)) {
            \Minz\Log::warning("Can’t send inactivity email to user {$user_id} (not inactive)");
            return false;
        }

        if ($user->deletion_notified_at) {
            \Minz\Log::warning("Can’t send inactivity email to user {$user_id} (already notified)");
            return false;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $brand = \App\Configuration::$application['brand'];
        $subject = sprintf(_('[%s] Your account will be deleted soon due to inactivity'), $brand);
        $this->setBody(
            'mailers/users/inactivity_email.phtml',
            'mailers/users/inactivity_email.txt',
            [
                'brand' => $brand,
                'username' => $user->username,
            ]
        );
        return $this->send($user->email, $subject);
    }
}
