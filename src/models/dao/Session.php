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

    /**
     * Delete sessions by user id.
     *
     * @param string $user_id
     * @param string $except_session_id
     *     To allow to reset all sessions except the current one.
     */
    public function deleteByUserId($user_id, $except_session_id = null)
    {
        $sql = <<<'SQL'
            DELETE FROM sessions
            WHERE user_id = :user_id
        SQL;
        $values = [
            ':user_id' => $user_id,
        ];

        if ($except_session_id) {
            $sql .= ' AND id != :session_id';
            $values[':session_id'] = $except_session_id;
        }

        $statement = $this->prepare($sql);
        return $statement->execute($values);
    }
}
