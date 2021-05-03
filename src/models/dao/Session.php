<?php

namespace flusio\models\dao;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Session extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\Session::PROPERTIES);
        parent::__construct('sessions', 'id', $properties);
    }

    /**
     * Delete sessions that have expired (no token).
     *
     * @return boolean True on success
     */
    public function deleteExpired()
    {
        $sql = <<<SQL
            DELETE FROM sessions
            WHERE token IS NULL
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute();
    }
}
