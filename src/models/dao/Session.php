<?php

namespace App\models\dao;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Session
{
    public static function findByTokenId(string $token_id, string $scope): ?self
    {
        $sql = <<<SQL
            SELECT s.* FROM sessions s, tokens t
            WHERE s.scope = :scope
            AND t.token = s.token
            AND t.token = :token_id
            AND t.expired_at > :expired_at
            AND t.invalidated_at IS NULL
        SQL;

        $now = \Minz\Time::now();

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':scope' => $scope,
            ':token_id' => $token_id,
            ':expired_at' => $now->format(Database\Column::DATETIME_FORMAT),
        ]);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }

    /**
     * Delete sessions that have expired (no token).
     */
    public static function deleteExpired(): bool
    {
        $sql = <<<SQL
            DELETE FROM sessions
            WHERE token IS NULL
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute();
    }

    /**
     * Delete sessions by user id.
     *
     * @param string $except_session_id
     *     To allow to reset all sessions except the current one.
     */
    public static function deleteByUserId(string $user_id, ?string $except_session_id = null): bool
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

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($values);
    }

    /**
     * Return the number of active sessions (1 max per users) since the given
     * date.
     */
    public static function countUsersActiveSince(\DateTimeImmutable $since): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(DISTINCT user_id) FROM sessions
            WHERE token IS NOT NULL
            AND created_at >= :since
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':since' => $since->format(Database\Column::DATETIME_FORMAT),
        ]);

        return intval($statement->fetchColumn());
    }
}
