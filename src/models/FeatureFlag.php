<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeatureFlag extends \Minz\Model
{
    use DaoConnector;

    public const VALID_TYPES = ['feeds'];

    public const PROPERTIES = [
        'id' => [
            'type' => 'integer',
        ],

        'created_at' => [
            'type' => 'datetime',
        ],

        'type' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\FeatureFlag::validateType',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * Return the user associated to the feature flag.
     *
     * @return \flusio\models\User
     */
    public function user()
    {
        return User::find($this->user_id);
    }

    /**
     * @param string $type
     *
     * @return boolean
     */
    public static function validateType($type)
    {
        return in_array($type, self::VALID_TYPES);
    }
}
