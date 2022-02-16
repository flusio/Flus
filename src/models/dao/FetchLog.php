<?php

namespace flusio\models\dao;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FetchLog extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        $properties = array_keys(\flusio\models\FetchLog::PROPERTIES);
        parent::__construct('fetch_logs', 'id', $properties);
    }

    /**
     * Return the number of fetch_logs by type
     *
     * @param string $type
     *
     * @return integer
     */
    public function countByType($type)
    {
        $sql = <<<'SQL'
            SELECT COUNT(*) FROM fetch_logs
            WHERE type = ?
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$type]);
        return intval($statement->fetchColumn());
    }

    /**
     * Return the number of fetch_logs by days.
     *
     * @return integer[]
     */
    public function countByDays()
    {
        $sql = <<<'SQL'
            SELECT TO_CHAR(created_at, 'yyyy-mm-dd') AS day, COUNT(*) as count
            FROM fetch_logs
            GROUP BY day
        SQL;

        $statement = $this->query($sql);
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
    public function countFetchesToHost($host, $since, $type = null, $ip = null)
    {
        $since = $since->format(\Minz\Model::DATETIME_FORMAT);
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

        $statement = $this->prepare($sql);
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
     *
     * @return integer
     */
    public function countEstimated()
    {
        $sql = <<<SQL
            SELECT reltuples AS count
            FROM pg_class
            WHERE relname = '{$this->table_name}';
        SQL;

        $statement = $this->query($sql);
        return intval($statement->fetchColumn());
    }

    /**
     * Delete logs older than the given date
     *
     * @param \DateTime $date
     *
     * @return boolean True on success
     */
    public function deleteOlderThan($date)
    {
        $sql = <<<SQL
            DELETE FROM fetch_logs
            WHERE created_at < ?
        SQL;

        $statement = $this->prepare($sql);
        return $statement->execute([
            $date->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }
}
