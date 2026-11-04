<?php

namespace App\models\users;

use Minz\Database;

/**
 * Add methods to count the users of the instance.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Statistics
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
}
