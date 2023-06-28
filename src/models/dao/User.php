<?php

namespace flusio\models\dao;

use Minz\Database;

/**
 * Represent a user of flusio in database.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait User
{
    /**
     * Return the number of validated users.
     */
    public static function countValidated(): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM users
            WHERE validated_at IS NOT NULL
        SQL;

        $database = Database::get();
        $statement = $database->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of users created since the given date.
     */
    public static function countSince(\DateTimeImmutable $since): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM users
            WHERE created_at >= ?
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            $since->format(Database\Column::DATETIME_FORMAT),
        ]);

        return intval($statement->fetchColumn());
    }

    /**
     * Delete not validated users older than the given date.
     */
    public static function deleteNotValidatedOlderThan(\DateTimeImmutable $date): bool
    {
        $sql = <<<SQL
            DELETE FROM users
            WHERE validated_at IS NULL
            AND created_at < ?
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute([
            $date->format(Database\Column::DATETIME_FORMAT),
        ]);
    }

    /**
     * Return a user by its session token.
     *
     * The token must not be invalidated, and should not have expired. In these
     * cases, we return `null`. `null` is also returned if there are more than
     * one result, which cannot happen theorically since the `sessions.token`
     * column must be unique in database.
     */
    public static function findBySessionToken(string $token): ?self
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

        $now = \Minz\Time::now();

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            $token,
            $now->format(Database\Column::DATETIME_FORMAT),
        ]);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }

    /**
     * Return users with subscriptions expired_at before a given date. It does
     * not return users with no account id (you should create them first).
     *
     * @return self[]
     */
    public static function listBySubscriptionExpiredAtBefore(\DateTimeImmutable $before_this_date): array
    {
        $sql = <<<'SQL'
            SELECT * FROM users
            WHERE subscription_expired_at <= ?
            AND subscription_account_id IS NOT NULL
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            $before_this_date->format(Database\Column::DATETIME_FORMAT),
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
