<?php

namespace flusio\models;

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
            'id' => bin2hex(random_bytes(16)),
            'name' => trim($name),
            'ip' => trim($ip),
        ]);
    }

    /**
     * Initialize a Session from values.
     *
     * @param array $values
     */
    public function __construct($values)
    {
        parent::__construct(self::PROPERTIES);
        $this->fromValues($values);
    }
}
