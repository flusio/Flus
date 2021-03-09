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
    ];

    /**
     * Initialize the model with default values.
     *
     * @param mixed $values
     */
    public function __construct($values)
    {
        parent::__construct(array_merge([
            'id' => utils\Random::hex(32),
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
     * Sort topics based on given locale
     *
     * @param \flusio\models\Topic[] $topics
     * @param string $locale
     */
    public static function sort(&$topics, $locale)
    {
        $collator = new \Collator($locale);
        usort($topics, function ($topic1, $topic2) use ($collator) {
            return $collator->compare($topic1->label, $topic2->label);
        });
    }

    /**
     * @param string $label
     * @return boolean
     */
    public static function validateLabel($label)
    {
        return strlen($label) <= self::LABEL_MAX_SIZE;
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
                $formatted_error = vsprintf(
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
