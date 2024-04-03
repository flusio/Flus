<?php

namespace App\models\dao;

use App\models;
use Minz\Database;

/**
 * Represent a user of Flus in database.
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
     * Return the number of new users per month for the given year.
     *
     * It excludes users that are not validated.
     *
     * @return array<string, int>
     */
    public static function countPerMonth(int $year): array
    {
        $sql = <<<'SQL'
            SELECT to_char(created_at, 'YYYY-MM') AS date, COUNT(*) FROM users
            WHERE id != :support_user_id
            AND created_at >= :since
            AND created_at <= :until
            AND validated_at IS NOT NULL
            GROUP BY date
        SQL;

        $since = new \DateTimeImmutable();
        $since = $since->setDate($year, 1, 1);
        $since = $since->setTime(0, 0, 0);

        $until = $since->setDate($year, 12, 31);
        $until = $until->setTime(23, 59, 59);

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':support_user_id' => models\User::supportUser()->id,
            ':since' => $since->format(Database\Column::DATETIME_FORMAT),
            ':until' => $until->format(Database\Column::DATETIME_FORMAT),
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Return the number of active users per month for the given year.
     *
     * An active user is a user that created a link during a given month.
     * It excludes users that are not validated.
     *
     * @return array<string, int>
     */
    public static function countActivePerMonth(int $year): array
    {
        $sql = <<<'SQL'
            SELECT to_char(created_at, 'YYYY-MM') AS date, COUNT(DISTINCT user_id) FROM links
            WHERE user_id IN (
                SELECT id FROM users
                WHERE id != :support_user_id
                AND validated_at IS NOT NULL
            )
            AND created_at >= :since
            AND created_at <= :until
            GROUP BY date
        SQL;

        $since = new \DateTimeImmutable();
        $since = $since->setDate($year, 1, 1);
        $since = $since->setTime(0, 0, 0);

        $until = $since->setDate($year, 12, 31);
        $until = $until->setTime(23, 59, 59);

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':support_user_id' => models\User::supportUser()->id,
            ':since' => $since->format(Database\Column::DATETIME_FORMAT),
            ':until' => $until->format(Database\Column::DATETIME_FORMAT),
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
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
