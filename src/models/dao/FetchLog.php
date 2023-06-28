<?php

namespace flusio\models\dao;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FetchLog
{
    /**
     * Return the number of fetch_logs by type
     */
    public static function countByType(string $type): int
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM fetch_logs
            WHERE type = ?
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$type]);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of fetch_logs by days.
     *
     * @return array<string, int>
     */
    public static function countByDays(): array
    {
        $sql = <<<'SQL'
            SELECT TO_CHAR(created_at, 'yyyy-mm-dd') AS day, COUNT(*) as count
            FROM fetch_logs
            GROUP BY day
        SQL;

        $database = Database::get();
        $statement = $database->query($sql);
        $result = $statement->fetchAll();

        $count_by_days = [];
        foreach ($result as $row) {
            $count_by_days[$row['day']] = intval($row['count']);
        }

        ksort($count_by_days);

        return $count_by_days;
    }

    /**
     * Return the number of calls to a host since a given time.
     *
     * @param string $host
     * @param \DateTime $since
     * @param string $type (optional)
     * @param string $ip (optional)
     *
     * @return integer
     */
    public static function countFetchesToHost(
        string $host,
        \DateTimeImmutable $since,
        ?string $type = null,
        ?string $ip = null
    ): int {
        $since = $since->format(Database\Column::DATETIME_FORMAT);
        $values = [
            ':host' => $host,
            ':since' => $since,
        ];

        $type_placeholder = '';
        if ($type !== null) {
            $type_placeholder = 'AND type = :type';
            $values[':type'] = $type;
        }

        $ip_placeholder = '';
        if ($ip !== null) {
            $ip_placeholder = 'AND (ip = :ip OR ip IS NULL)';
            $values[':ip'] = $ip;
        }

        $sql = <<<SQL
            SELECT COUNT(*) FROM fetch_logs
            WHERE host = :host
            AND created_at >= :since
            {$type_placeholder}
            {$ip_placeholder}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);

        return intval($statement->fetchColumn());
    }

    /**
     * Return an estimated number of logs.
     *
     * This method have better performance than basic count but is less
     * precise.
     *
     * @see https://wiki.postgresql.org/wiki/Count_estimate
     */
    public static function countEstimated(): int
    {
        $table_name = self::tableName();

        $sql = <<<SQL
            SELECT reltuples AS count
            FROM pg_class
            WHERE relname = ?;
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([$table_name]);
        return intval($statement->fetchColumn());
    }

    /**
     * Delete logs older than the given date
     */
    public static function deleteOlderThan(\DateTimeImmutable $date): bool
    {
        $sql = <<<SQL
            DELETE FROM fetch_logs
            WHERE created_at < ?
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute([
            $date->format(Database\Column::DATETIME_FORMAT),
        ]);
    }
}
