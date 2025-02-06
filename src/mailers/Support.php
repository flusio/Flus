<?php

namespace App\mailers;

use App\models;
use App\utils;
use Minz\Mailer;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Support extends Mailer
{
    /**
     * Send an email to the support user.
     */
    public function sendMessage(string $user_id, string $subject, string $message): ?Mailer\Email
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send message from user {$user_id} (not found)");
            return null;
        }

        $support_user = models\User::supportUser();
        utils\Locale::setCurrentLocale($support_user->locale);

        $email = new Mailer\Email();

        $brand = \App\Configuration::$application['brand'];
        $email->setSubject(sprintf(_('[%s] Contact: %s'), $brand, $subject));
        $email->setBody(
            'mailers/support/message.phtml',
            'mailers/support/message.txt',
            [
                'message' => $message,
            ]
        );
        $email->addReplyTo($user->email);

        $this->send($email, to: $support_user->email);

        return $email;
    }

    /**
     * Send a notification to the user.
     */
    public function sendNotification(string $user_id, string $subject): ?Mailer\Email
    {
        $user = models\User::find($user_id);

        if (!$user) {
            \Minz\Log::warning("Can’t send notification to user {$user_id} (not found)");
            return null;
        }

        utils\Locale::setCurrentLocale($user->locale);

        $email = new Mailer\Email();

        $logo_path = \App\Configuration::$app_path . '/public/static/logo.svg';
        $logo_cid = md5($logo_path);
        $email->addEmbeddedImage($logo_path, $logo_cid);

        $brand = \App\Configuration::$application['brand'];
        $email->setSubject(sprintf(_('[%s] Your message has been sent'), $brand));
        $email->setBody(
            'mailers/support/notification.phtml',
            'mailers/support/notification.txt',
            [
                'logo_cid' => $logo_cid,
                'username' => $user->username,
                'subject' => $subject,
            ]
        );

        $this->send($email, to: $user->email);

        return $email;
    }
}
