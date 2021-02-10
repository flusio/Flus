<?php

namespace flusio\models;

use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Token extends \Minz\Model
{
    use DaoConnector;

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
        ],
    ];

    /**
     * Initialize a token valid for a certain amount of time.
     *
     * @see \Minz\Time
     *
     * @param integer $number
     * @param string $duration
     * @param integer $length default is 64
     *
     * @return \flusio\models\Token
     */
    public static function init($number, $duration, $length = 64)
    {
        return new self([
            'expired_at' => \Minz\Time::fromNow($number, $duration),
            'token' => utils\Random::hex($length),
        ]);
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
     * Return whether the token has been invalidated.
     *
     * @return boolean
     */
    public function isInvalidated()
    {
        return $this->invalidated_at !== null;
    }

    /**
     * Return whether the token is valid (i.e. not expired and not invalidated)
     *
     * @return boolean
     */
    public function isValid()
    {
        return !$this->hasExpired() && !$this->isInvalidated();
    }

    /**
     * Return wheter the token is going to expire in the next $number of $units.
     *
     * @see https://www.php.net/manual/datetime.formats.relative.php
     *
     * @param integer $number
     * @param string $unit
     *
     * @return boolean
     */
    public function expiresIn($number, $unit)
    {
        return \Minz\Time::fromNow($number, $unit) >= $this->expired_at;
    }
}
