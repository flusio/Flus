<?php

namespace flusio\models;

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
    ];

    /**
     * @param string $username
     * @param string $email
     * @param string $password
     *
     * @throws \Minz\Error\ModelPropertyError if one of the property is invalid
     */
    public static function init($username, $email, $password)
    {
        return new self([
            'id' => bin2hex(random_bytes(16)),
            'username' => trim($username),
            'email' => strtolower(self::emailToPunycode(trim($email))),
            'password_hash' => $password ? password_hash($password, PASSWORD_BCRYPT) : '',
        ]);
    }

    /**
     * Initialize a User from values (usually from database).
     *
     * @param array $values
     *
     * @throws \Minz\Error\ModelPropertyError if one of the value is invalid
     */
    public function __construct($values)
    {
        parent::__construct(self::PROPERTIES);
        $this->fromValues($values);
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
     * @see https://en.wikipedia.org/wiki/Punycode
     *
     * @param string $email
     *
     * @param string
     */
    public static function emailToPunycode($email)
    {
        $at_position = strrpos($email, '@');

        if ($at_position === false || !function_exists('idn_to_ascii')) {
            return $email;
        }

        $domain = substr($email, $at_position + 1);
        $domain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if ($domain !== false) {
            $email = substr($email, 0, $at_position + 1) . $domain;
        }

        return $email;
    }

    /**
     * @param string $email
     * @return boolean
     */
    public static function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * @param string $username
     * @return boolean
     */
    public static function validateUsername($username)
    {
        return strlen($username) <= 50;
    }
}
