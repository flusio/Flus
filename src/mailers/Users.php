<?php

namespace App\mailers;

use App\models;
use App\utils;
use Minz\Mailer;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Users extends Mailer
{
    private string $logo_path;

    private string $logo_cid;

    public function __construct()
    {
        $this->logo_path = \App\Configuration::$app_path . '/public/static/logo.svg';
        $this->logo_cid = md5($this->logo_path);
    }

    /**
     * Send an email to given user to validate its account.
     */
    public function sendAccountValidationEmail(string $user_id): ?Mailer\Email
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send validation email to user {$user_id} (not found)");
            return null;
        }

        if (!$user->validation_token) {
            \Minz\Log::warning("Can’t send validation email to user {$user_id} (no token)");
            return null;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $email = new Mailer\Email();

        $brand = \App\Configuration::$application['brand'];
        $email->setSubject(sprintf(_('[%s] Confirm your account'), $brand));
        $email->setBody(
            'mailers/users/account_validation_email.html.twig',
            'mailers/users/account_validation_email.txt.twig',
            [
                'logo_cid' => $this->logo_cid,
                'username' => $user->username,
                'token' => $user->validation_token,
            ]
        );
        $email->addEmbeddedImage($this->logo_path, $this->logo_cid, type: 'image/svg+xml');

        $this->send($email, to: $user->email);

        return $email;
    }

    /**
     * Send an email to the given user to reset its password.
     */
    public function sendResetPasswordEmail(string $user_id): ?Mailer\Email
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send reset email to user {$user_id} (not found)");
            return null;
        }

        if (!$user->reset_token) {
            \Minz\Log::warning("Can’t send reset email to user {$user_id} (no token)");
            return null;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $email = new Mailer\Email();

        $brand = \App\Configuration::$application['brand'];
        $email->setSubject(sprintf(_('[%s] Reset your password'), $brand));
        $email->setBody(
            'mailers/users/reset_password_email.html.twig',
            'mailers/users/reset_password_email.txt.twig',
            [
                'logo_cid' => $this->logo_cid,
                'username' => $user->username,
                'token' => $user->reset_token,
            ]
        );
        $email->addEmbeddedImage($this->logo_path, $this->logo_cid, type: 'image/svg+xml');

        $this->send($email, to: $user->email);

        return $email;
    }

    /**
     * Send an email to the given user to warn about its inactivity and the
     * upcoming deletion of its account.
     */
    public function sendInactivityEmail(string $user_id): ?Mailer\Email
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send inactivity email to user {$user_id} (not found)");
            return null;
        }

        if (!$user->isInactive(months: 11)) {
            \Minz\Log::warning("Can’t send inactivity email to user {$user_id} (not inactive)");
            return null;
        }

        if ($user->deletion_notified_at) {
            \Minz\Log::warning("Can’t send inactivity email to user {$user_id} (already notified)");
            return null;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $email = new Mailer\Email();

        $brand = \App\Configuration::$application['brand'];
        $email->setSubject(sprintf(_('[%s] Your account will be deleted soon due to inactivity'), $brand));
        $email->setBody(
            'mailers/users/inactivity_email.html.twig',
            'mailers/users/inactivity_email.txt.twig',
            [
                'logo_cid' => $this->logo_cid,
                'username' => $user->username,
            ]
        );
        $email->addEmbeddedImage($this->logo_path, $this->logo_cid, type: 'image/svg+xml');

        $this->send($email, to: $user->email);

        return $email;
    }
}
