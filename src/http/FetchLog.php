<?php

namespace App\http;

use App\utils;
use Minz\Database;

/**
 * Represent an HTTP request log made to an external website.
 *
 * It is useful to make statistics and to rate limit the requests.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'fetch_logs')]
class FetchLog
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $url;

    #[Database\Column]
    public string $host;

    #[Database\Column]
    public string $type;

    #[Database\Column]
    public ?string $ip;

    /**
     * Create a log in DB for the given URL.
     */
    public static function log(string $url, string $type, ?string $ip = null): void
    {
        $fetch_log = new self();

        $fetch_log->url = $url;
        $fetch_log->host = utils\Belt::host($url);
        $fetch_log->type = $type;
        $fetch_log->ip = $ip;

        $fetch_log->save();
    }

    /**
     * Return the number of fetch_logs by type.
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
            /** @var string */
            $day = $row['day'];
            $count_by_days[$day] = intval($row['count']);
        }

        ksort($count_by_days);

        return $count_by_days;
    }

    /**
     * Return the number of calls to the url's host since a given time.
     */
    public static function countFetchesToHost(
        string $url,
        \DateTimeImmutable $since,
        ?string $type = null,
        ?string $ip = null
    ): int {
        $since = $since->format(Database\Column::DATETIME_FORMAT);
        $values = [
            ':host' => utils\Belt::host($url),
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
