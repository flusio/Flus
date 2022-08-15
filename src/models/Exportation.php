<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Exportation extends \Minz\Model
{
    use DaoConnector;

    public const VALID_STATUSES = ['ongoing', 'finished', 'error'];

    public const PROPERTIES = [
        'id' => [
            'type' => 'integer',
        ],

        'created_at' => [
            'type' => 'datetime',
        ],

        'status' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Exportation::validateStatus',
        ],

        'error' => [
            'type' => 'string',
        ],

        'filepath' => [
            'type' => 'string',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * @param string $user_id
     *
     * @return \flusio\models\Exportation
     */
    public static function init($user_id)
    {
        return new self([
            'status' => 'ongoing',
            'error' => '',
            'filepath' => '',
            'user_id' => $user_id,
        ]);
    }

    /**
     * Stop and mark the exportation as finished
     *
     * @param string $filepath
     */
    public function finish($filepath)
    {
        $this->status = 'finished';
        $this->filepath = $filepath;
    }

    /**
     * Stop and mark the exportation as failed
     *
     * @param string $error
     */
    public function fail($error)
    {
        $this->status = 'error';
        $this->error = $error;
    }

    /**
     * @param string $status
     *
     * @return boolean
     */
    public static function validateStatus($status)
    {
        return in_array($status, self::VALID_STATUSES);
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
