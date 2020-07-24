<?php

namespace flusio\models\dao;

/**
 * Represent a message that comment a link in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Message extends \Minz\DatabaseModel
{
    use SaveHelper;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Message::PROPERTIES);
        parent::__construct('messages', 'id', $properties);
    }
}
