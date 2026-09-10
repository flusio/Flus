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
            WHERE created_at >= :since
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
                WHERE validated_at IS NOT NULL
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
            ':since' => $since->format(Database\Column::DATETIME_FORMAT),
            ':until' => $until->format(Database\Column::DATETIME_FORMAT),
        ]);

        return $statement->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

    /**
     * Delete the users who are not validated
     */
    public static function deleteNotValidatedOlderThan(\DateTimeImmutable $since): bool
    {
        $sql = <<<SQL
            DELETE FROM users
            WHERE validated_at IS NULL
            AND created_at < :since
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute([
            ':since' => $since->format(Database\Column::DATETIME_FORMAT),
        ]);
    }

    /**
     * Return the number of users active since the given date.
     */
    public static function countActiveSince(\DateTimeImmutable $since): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM users
            WHERE last_activity_at >= ?
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            $since->format(Database\Column::DATETIME_FORMAT),
        ]);

        return intval($statement->fetchColumn());
    }

    /**
     * Delete the inactive users that have been notified about it.
     *
     * If $except_subscribed is true, the users with an active subscription are
     * not deleted.
     */
    public static function deleteInactiveAndNotified(
        \DateTimeImmutable $inactive_since,
        \DateTimeImmutable $notified_since,
        bool $except_subscribed = false,
    ): bool {
        $sql = <<<SQL
            DELETE FROM users
            WHERE last_activity_at <= :inactive_since
            AND deletion_notified_at <= :notified_since
        SQL;

        $parameters = [
            ':inactive_since' => $inactive_since->format(Database\Column::DATETIME_FORMAT),
            ':notified_since' => $notified_since->format(Database\Column::DATETIME_FORMAT),
        ];

        if ($except_subscribed) {
            [$overdue_clause, $overdue_parameters] = self::sqlOverdueSubscription();
            $sql .= $overdue_clause;
            $parameters += $overdue_parameters;
        }

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($parameters);
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

    /**
     * Return the users that haven't be active since the given date.
     *
     * If $except_subscribed is true, the users with an active subscription are
     * not returned.
     *
     * @return self[]
     */
    public static function listInactiveAndNotNotified(
        \DateTimeImmutable $inactive_since,
        bool $except_subscribed = false,
    ): array {
        $sql = <<<'SQL'
            SELECT * FROM users
            WHERE last_activity_at <= :inactive_since
            AND deletion_notified_at IS NULL
        SQL;

        $parameters = [
            ':inactive_since' => $inactive_since->format(Database\Column::DATETIME_FORMAT),
        ];

        if ($except_subscribed) {
            [$overdue_clause, $overdue_parameters] = self::sqlOverdueSubscription();
            $sql .= $overdue_clause;
            $parameters += $overdue_parameters;
        }

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Return a SQL clause (and its parameters) matching the users whose
     * subscription is overdue (i.e. neither active nor exempted).
     *
     * @return array{string, array<string, string>}
     */
    private static function sqlOverdueSubscription(): array
    {
        $sql = ' AND subscription_expired_at <= :now AND subscription_expired_at > :exempted_date';

        $exempted_date = new \DateTimeImmutable('@0');
        $parameters = [
            ':now' => \Minz\Time::now()->format(Database\Column::DATETIME_FORMAT),
            ':exempted_date' => $exempted_date->format(Database\Column::DATETIME_FORMAT),
        ];

        return [$sql, $parameters];
    }
}
