<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Importation extends \Minz\Model
{
    use DaoConnector;

    public const VALID_TYPES = ['pocket'];
    public const VALID_STATUSES = ['ongoing', 'finished', 'error'];

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
            'validator' => '\flusio\models\Importation::validateType',
        ],

        'status' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Importation::validateStatus',
        ],

        'options' => [
            'type' => 'string',
            'required' => true,
        ],

        'error' => [
            'type' => 'string',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * @param string $type
     * @param string $user_id
     *
     * @return \flusio\models\Importation
     */
    public static function init($type, $user_id)
    {
        return new self([
            'type' => $type,
            'status' => 'ongoing',
            'options' => '{}',
            'user_id' => $user_id,
            'error' => '',
        ]);
    }

    /**
     * Stop and mark the importation as finished
     */
    public function finish()
    {
        $this->status = 'finished';
    }

    /**
     * Stop and mark the importation as failed
     *
     * @param string $error
     */
    public function fail($error)
    {
        $this->status = 'error';
        $this->error = $error;
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
     * @param string $status
     *
     * @return boolean
     */
    public static function validateStatus($status)
    {
        return in_array($status, self::VALID_STATUSES);
    }
}
