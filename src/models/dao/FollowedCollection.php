<?php

namespace flusio\models\dao;

/**
 * Represent users following collections
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FollowedCollection extends \Minz\DatabaseModel
{
    use BulkQueries;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\FollowedCollection::PROPERTIES);
        parent::__construct('followed_collections', 'id', $properties);
    }
}
