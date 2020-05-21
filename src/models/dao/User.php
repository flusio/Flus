<?php

namespace flusio\models\dao;

/**
 * Represent a user of flusio in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class User extends \Minz\DatabaseModel
{
    use SaveHelper;

    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\User::PROPERTIES);
        parent::__construct('users', 'id', $properties);
    }

    /**
     * Return not validated users older than the given time.
     *
     * @see \Minz\Time::ago
     *
     * @param integer $number
     * @param string $unit
     *
     * @throws \Minz\Errors\DatabaseModelError if an error occured during the execution
     *
     * @return array
     */
    public function listNotValidatedOlderThan($number, $unit)
    {
        $date = \Minz\Time::ago($number, $unit)->format(\Minz\Model::DATETIME_FORMAT);
        $sql = "SELECT * FROM users WHERE validated_at IS NULL AND created_at <= ?";
        $statement = $this->prepare($sql);
        $result = $statement->execute([$date]);
        if (!$result) {
            throw self::sqlStatementError($statement); // @codeCoverageIgnore
        }

        $result = $statement->fetchAll();
        if ($result !== false) {
            return $result;
        } else {
            throw self::sqlStatementError($statement); // @codeCoverageIgnore
        }
    }

    /**
     * Return a user by its session token.
     *
     * The token must not be invalidated, and should not have expired. In these
     * cases, we return `null`. `null` is also returned if there are more than
     * one result, which cannot happen theorically since the `sessions.token`
     * column must be unique in database.
     *
     * @param string $token
     *
     * @throws \Minz\Errors\DatabaseModelError if an error occured during the execution
     *
     * @return array|null
     */
    public function findBySessionToken($token)
    {
        $sql = <<<'SQL'
            SELECT * FROM users WHERE id = (
                SELECT user_id FROM sessions WHERE token = (
                    SELECT token FROM tokens
                    WHERE token = ?
                    AND expired_at > ?
                    AND invalidated_at IS NULL
                )
            )
        SQL;

        $now = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);

        $statement = $this->prepare($sql);
        $result = $statement->execute([$token, $now]);
        if (!$result) {
            throw self::sqlStatementError($statement); // @codeCoverageIgnore
        }

        $result = $statement->fetchAll();
        if ($result !== false && count($result) === 1) {
            return $result[0];
        } elseif ($result !== false) {
            return null;
        } else {
            throw self::sqlStatementError($statement); // @codeCoverageIgnore
        }
    }
}
