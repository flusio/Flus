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
        $user_dao = new models\dao\User();
        $db_user = $user_dao->find($user_id);
        if (!$db_user) {
            \Minz\Log::warning("Can’t send validation email to user {$user_id} (not found)");
            return;
        }

        $user = new models\User($db_user);
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
}
