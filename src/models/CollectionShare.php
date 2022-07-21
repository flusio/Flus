<?php

namespace flusio\models;

use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionShare extends \Minz\Model
{
    use DaoConnector;

    public const VALID_TYPES = ['read', 'write'];

    public const PROPERTIES = [
        'id' => [
            'type' => 'integer',
        ],

        'created_at' => [
            'type' => 'datetime',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],

        'collection_id' => [
            'type' => 'string',
            'required' => true,
        ],

        'type' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\CollectionShare::validateType',
        ],

        // used to sort collection shares easily
        'username' => [
            'type' => 'string',
            'computed' => true,
        ],
    ];

    /**
     * Initialize the model with default values.
     *
     * @param mixed $values
     */
    public function __construct($values)
    {
        parent::__construct(array_merge([
            'type' => 'read',
        ], $values));
    }

    /**
     * @param string $user_id
     * @param string $collection_id
     * @param string $type
     *
     * @return \flusio\models\CollectionShare
     */
    public static function init($user_id, $collection_id, $type)
    {
        return new self([
            'user_id' => $user_id,
            'collection_id' => $collection_id,
            'type' => $type,
        ]);
    }

    /**
     * Return the user attached to the CollectionShare
     *
     * @return \flusio\models\User
     */
    public function user()
    {
        return User::find($this->user_id);
    }

    /**
     * @param string $type
     * @return boolean
     */
    public static function validateType($type)
    {
        return in_array($type, self::VALID_TYPES);
    }

    /**
     * Return a list of errors (if any). The array keys indicated the concerned
     * property.
     *
     * @return string[]
     */
    public function validate()
    {
        $formatted_errors = [];

        foreach (parent::validate() as $property => $error) {
            $code = $error['code'];

            if ($property === 'type' && $code === 'required') {
                $formatted_error = _('The type is required.');
            } elseif ($property === 'type') {
                $formatted_error = _('The type is invalid.');
            } else {
                $formatted_error = $error['description']; // @codeCoverageIgnore
            }

            $formatted_errors[$property] = $formatted_error;
        }

        return $formatted_errors;
    }
}
