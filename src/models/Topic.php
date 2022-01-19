<?php

namespace flusio\models;

use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Topic extends \Minz\Model
{
    use DaoConnector;

    public const LABEL_MAX_SIZE = 30;

    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => [
            'type' => 'datetime',
        ],

        'label' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Topic::validateLabel',
        ],

        'image_filename' => [
            'type' => 'string',
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
            'id' => utils\Random::timebased(),
            'label' => '',
        ], $values));
    }

    /**
     * @param string $label
     */
    public static function init($label)
    {
        return new self([
            'label' => trim($label),
        ]);
    }

    /**
     * Return the number of public collections attached to this topic
     *
     * @return integer
     */
    public function countPublicCollections()
    {
        return Collection::daoCall('countPublicByTopicId', $this->id);
    }

    /**
     * @param string $label
     * @return boolean
     */
    public static function validateLabel($label)
    {
        return mb_strlen($label) <= self::LABEL_MAX_SIZE;
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

            if ($property === 'label' && $code === 'required') {
                $formatted_error = _('The label is required.');
            } elseif ($property === 'label') {
                $formatted_error = sprintf(
                    _('The label must be less than %d characters.'),
                    self::LABEL_MAX_SIZE
                );
            } else {
                $formatted_error = $error['description']; // @codeCoverageIgnore
            }

            $formatted_errors[$property] = $formatted_error;
        }

        return $formatted_errors;
    }
}
