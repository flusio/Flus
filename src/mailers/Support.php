<?php

namespace flusio\mailers;

use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Support extends \Minz\Mailer
{
    /**
     * Send an email to the support user.
     */
    public function sendMessage(string $user_id, string $subject, string $message): bool
    {
        $user = models\User::find($user_id);
        if (!$user) {
            \Minz\Log::warning("Can’t send message from user {$user_id} (not found)");
            return false;
        }

        $support_user = models\User::supportUser();
        utils\Locale::setCurrentLocale($support_user->locale);

        /** @var string */
        $brand = \Minz\Configuration::$application['brand'];
        $current_subject = sprintf(_('[%s] Contact: %s'), $brand, $subject);
        $this->setBody(
            'mailers/support/message.phtml',
            'mailers/support/message.txt',
            [
                'message' => $message,
            ]
        );
        $this->mailer->addReplyTo($user->email);

        return $this->send($support_user->email, $current_subject);
    }

    /**
     * Send a notification to the user.
     */
    public function sendNotification(string $user_id, string $subject): bool
    {
        $user = models\User::find($user_id);

        if (!$user) {
            \Minz\Log::warning("Can’t send notification to user {$user_id} (not found)");
            return false;
        }

        utils\Locale::setCurrentLocale($user->locale);

        /** @var string */
        $brand = \Minz\Configuration::$application['brand'];
        $current_subject = sprintf(_('[%s] Your message has been sent'), $brand);
        $this->setBody(
            'mailers/support/notification.phtml',
            'mailers/support/notification.txt',
            [
                'subject' => $subject,
                'brand' => $brand,
            ]
        );

        return $this->send($user->email, $current_subject);
    }
}
