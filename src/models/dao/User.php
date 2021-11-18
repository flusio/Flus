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
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\User::PROPERTIES);
        parent::__construct('users', 'id', $properties);
    }

    /**
     * Return the number of validated users.
     *
     * @return integer
     */
    public function countValidated()
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM users
            WHERE validated_at IS NOT NULL
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of users created since the given date.
     *
     * @param \DateTime $since
     *
     * @return integer
     */
    public function countSince($since)
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM users
            WHERE created_at >= ?
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$since->format(\Minz\Model::DATETIME_FORMAT)]);
        return intval($statement->fetchColumn());
    }

    /**
     * Delete not validated users older than the given date.
     *
     * @param \DateTime $date
     *
     * @return boolean True on success
     */
    public function deleteNotValidatedOlderThan($date)
    {
        $sql = <<<SQL
            DELETE FROM users
            WHERE validated_at IS NULL
            AND created_at < ?
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute([
            $date->format(\Minz\Model::DATETIME_FORMAT),
        ]);
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
        $statement->execute([$token, $now]);
        $result = $statement->fetchAll();
        if (count($result) === 1) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Return users with subscriptions expired_at before a given date. It does
     * not return users with no account id (you should create them first).
     *
     * @param \DateTime $before_this_date
     *
     * @return array
     */
    public function listBySubscriptionExpiredAtBefore($before_this_date)
    {
        $sql = <<<'SQL'
            SELECT * FROM users
            WHERE subscription_expired_at <= ?
            AND subscription_account_id IS NOT NULL
        SQL;
        $statement = $this->prepare($sql);
        $statement->execute([
            $before_this_date->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        return $statement->fetchAll();
    }
}
