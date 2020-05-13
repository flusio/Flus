<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Token extends \Minz\Model
{
    public const PROPERTIES = [
        'created_at' => 'datetime',

        'invalidated_at' => 'datetime',

        'expired_at' => [
            'type' => 'datetime',
            'required' => true,
        ],

        'token' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Token::validateToken',
        ],
    ];

    /**
     * Initialize a token
     */
    public static function init()
    {
        return new self([
            'expired_at' => \Minz\Time::fromNow(1, 'day'),
            'token' => bin2hex(random_bytes(8)),
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
     * Return whether the token has expired.
     *
     * @return boolean
     */
    public function hasExpired()
    {
        return \Minz\Time::now() >= $this->expired_at;
    }

    /**
     * @param string $token
     * @return boolean
     */
    public static function validateToken($token)
    {
        return strlen($token) >= 16;
    }
}
