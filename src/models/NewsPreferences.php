<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsPreferences extends \Minz\Model
{
    public const MIN_DURATION = 15;
    public const MAX_DURATION = 8 * 60;

    public const PROPERTIES = [
        'duration' => [
            'type' => 'integer',
            'required' => true,
            'validator' => '\flusio\models\NewsPreferences::validateDuration',
        ],

        'from_bookmarks' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'from_followed' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'from_topics' => [
            'type' => 'boolean',
            'required' => true,
        ],
    ];

    public const DEFAULT_VALUES = [
        'duration' => 30,
        'from_bookmarks' => true,
        'from_followed' => true,
        'from_topics' => true,
    ];

    /**
     * @param integer $duration must be between MIN_DURATION and MAX_DURATION
     * @param boolean $from_bookmarks
     * @param boolean $from_followed
     * @param boolean $from_topics
     *
     * @return \flusio\models\NewsPreferences
     */
    public static function init($duration, $from_bookmarks, $from_followed, $from_topics)
    {
        return new self([
            'duration' => intval($duration),
            'from_bookmarks' => filter_var($from_bookmarks, FILTER_VALIDATE_BOOLEAN),
            'from_followed' => filter_var($from_followed, FILTER_VALIDATE_BOOLEAN),
            'from_topics' => filter_var($from_topics, FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    /**
     * Export the values as a JSON string
     *
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->toValues());
    }

    /**
     * Initialize a NewsPreferences model from a JSON string
     *
     * @param string $json
     *
     * @return \flusio\models\NewsPreferences
     */
    public static function fromJson($json)
    {
        $values = json_decode($json, true);
        if (!$values) {
            $values = []; // @codeCoverageIgnore
        }
        $values = array_merge(self::DEFAULT_VALUES, $values);

        return new self($values);
    }

    /**
     * Check duration is between MIN_DURATION and MAX_DURATION.
     *
     * @param integer $duration
     *
     * @return boolean
     */
    public static function validateDuration($duration)
    {
        return self::MIN_DURATION <= $duration && $duration <= self::MAX_DURATION;
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

            if ($property === 'duration') {
                $min = self::MIN_DURATION;
                $max = self::MAX_DURATION;
                $formatted_error = _f('The duration must be between %d and %d.', $min, $max);
            } else {
                $formatted_error = $error['description']; // @codeCoverageIgnore
            }

            $formatted_errors[$property] = $formatted_error;
        }

        if (!$this->from_bookmarks && !$this->from_followed && !$this->from_topics) {
            $formatted_errors['from'] = _('You must select at least one option.');
        }

        return $formatted_errors;
    }
}
