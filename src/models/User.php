<?php

namespace flusio\models;

use flusio\utils;

/**
 * Represent a user of flusio.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class User extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'validated_at' => 'datetime',

        'validation_token' => 'string',

        'email' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\User::validateEmail',
        ],

        'username' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\User::validateUsername',
        ],

        'password_hash' => [
            'type' => 'string',
            'required' => true,
        ],

        'locale' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\User::validateLocale',
        ],
    ];

    /**
     * @param string $username
     * @param string $email
     * @param string $password
     */
    public static function init($username, $email, $password)
    {
        return new self([
            'id' => bin2hex(random_bytes(16)),
            'username' => trim($username),
            'email' => utils\Email::sanitize($email),
            'password_hash' => $password ? password_hash($password, PASSWORD_BCRYPT) : '',
            'locale' => \flusio\utils\Locale::DEFAULT_LOCALE,
        ]);
    }

    /**
     * Compare a password to the stored hash.
     *
     * @param string $password
     *
     * @return boolean Return true if the password matches the hash, else false
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }

    /**
     * @param string $email
     * @return boolean
     */
    public static function validateEmail($email)
    {
        return utils\Email::validate($email);
    }

    /**
     * @param string $username
     * @return boolean
     */
    public static function validateUsername($username)
    {
        return strlen($username) <= 50;
    }

    /**
     * @param string $locale
     * @return boolean
     */
    public static function validateLocale($locale)
    {
        $available_locales = \flusio\utils\Locale::availableLocales();
        return isset($available_locales[$locale]);
    }
}
