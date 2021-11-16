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
    ];

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
}
