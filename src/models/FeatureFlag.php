<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeatureFlag extends \Minz\Model
{
    use DaoConnector;

    public const VALID_TYPES = ['beta'];

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
     * Return whether a flag is enabled for the given user
     *
     * @param string $type
     * @param string $user_id
     *
     * @return boolean
     */
    public static function isEnabled($type, $user_id)
    {
        return self::findBy([
            'type' => $type,
            'user_id' => $user_id,
        ]) !== null;
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

    /**
     * Return the list of declared properties values.
     *
     * It doesn't return the id property because it is automatically generated
     * by the database.
     *
     * @see \Minz\Model::toValues
     *
     * @return array
     */
    public function toValues()
    {
        $values = parent::toValues();
        unset($values['id']);
        return $values;
    }
}
