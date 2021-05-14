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
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Message::PROPERTIES);
        parent::__construct('messages', 'id', $properties);
    }

    /**
     * Return the link messages, orderer by creation date
     *
     * @param string $link_id
     *
     * @return array
     */
    public function listByLink($link_id)
    {
        $sql = <<<SQL
             SELECT * FROM messages
             WHERE link_id = ?
             ORDER BY created_at
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$link_id]);
        return $statement->fetchAll();
    }
}
