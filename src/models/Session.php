<?php

namespace flusio\models;

use flusio\utils;

/**
 * Represent a user login session.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Session extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'name' => [
            'type' => 'string',
            'required' => true,
        ],

        'ip' => [
            'type' => 'string',
            'required' => true,
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],

        'token' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * @param string $name
     * @param string $ip
     *
     * @return \flusio\models\Session
     */
    public static function init($name, $ip)
    {
        return new self([
            'id' => utils\Random::hex(32),
            'name' => trim($name),
            'ip' => trim($ip),
        ]);
    }
}
