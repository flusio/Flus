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
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = ['id', 'created_at', 'user_id', 'collection_id'];
        parent::__construct('followed_collections', 'id', $properties);
    }
}
