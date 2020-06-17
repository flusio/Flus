<?php

namespace flusio\models;

/**
 * Represent a list containing a set of links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collection extends \Minz\Model
{
    public const VALID_TYPES = ['bookmarked'];

    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'name' => [
            'type' => 'string',
            'required' => true,
        ],

        'type' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Collection::validateType',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * @param string $user_id
     *
     * @return \flusio\models\Collection
     */
    public static function initBookmarked($user_id)
    {
        return new self([
            'id' => bin2hex(random_bytes(16)),
            'name' => _('Bookmarked'),
            'type' => 'bookmarked',
            'user_id' => $user_id,
        ]);
    }

    /**
     * @param string $type
     * @return boolean
     */
    public static function validateType($type)
    {
        return in_array($type, self::VALID_TYPES);
    }
}
