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
     * Return the number of calls to a host since a given time.
     *
     * @param string $host
     * @param \DateTime $since
     *
     * @return integer
     */
    public function countFetchesToHost($host, $since)
    {
        $since = $since->format(\Minz\Model::DATETIME_FORMAT);

        $sql = <<<'SQL'
            SELECT COUNT(*) FROM fetch_logs
            WHERE host = ?
            AND created_at >= ?
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([$host, $since]);
        return intval($statement->fetchColumn());
    }
}
