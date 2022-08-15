<?php

namespace flusio\models;

use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FollowedCollection extends \Minz\Model
{
    use DaoConnector;
    use BulkDaoConnector;

    public const VALID_TIME_FILTERS = ['strict', 'normal', 'all'];

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

        'group_id' => [
            'type' => 'string',
        ],

        'time_filter' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\FollowedCollection::validateTimeFilter',
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
            'time_filter' => 'normal',
        ], $values));
    }

    /**
     * @param string $user_id
     * @param string $collection_id
     *
     * @return \flusio\models\FollowedCollection
     */
    public static function init($user_id, $collection_id)
    {
        return new self([
            'user_id' => $user_id,
            'collection_id' => $collection_id,
        ]);
    }

    /**
     * @param string $time_filter
     * @return boolean
     */
    public static function validateTimeFilter($time_filter)
    {
        return in_array($time_filter, self::VALID_TIME_FILTERS);
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

            if ($property === 'time_filter' && $code === 'required') {
                $formatted_error = _('The filter is required.');
            } elseif ($property === 'time_filter') {
                $formatted_error = _('The filter is invalid.');
            } else {
                $formatted_error = $error['description']; // @codeCoverageIgnore
            }

            $formatted_errors[$property] = $formatted_error;
        }

        return $formatted_errors;
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
