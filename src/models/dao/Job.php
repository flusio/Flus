<?php

namespace flusio\models\dao;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Job extends \Minz\DatabaseModel
{
    /**
     * @throws \Minz\Errors\DatabaseError
     */
    public function __construct()
    {
        parent::__construct('jobs', 'id', [
            'id',
            'created_at',
            'handler',
            'perform_at',
            'locked_at',
            'number_attempts',
            'last_error',
            'failed_at',
        ]);
    }

    /**
     * Return the next available job (i.e. to perform and not locked)
     *
     * @return array
     */
    public function findNextJob()
    {
        $sql = <<<'SQL'
            SELECT * FROM jobs
            WHERE locked_at IS NULL
            AND perform_at <= ?
            ORDER BY created_at;
        SQL;

        $now = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);

        $statement = $this->prepare($sql);
        $statement->execute([$now]);
        return $statement->fetch();
    }

    /**
     * Lock the given job.
     *
     * The lock fail if a lock is already set. This method also increment the
     * number_attempts value.
     *
     * @return boolean True if the lock suceeded, else false
     */
    public function lock($job_id)
    {
        $sql = <<<SQL
            UPDATE {$this->table_name}
            SET locked_at = ?, number_attempts = number_attempts + 1
            WHERE {$this->primary_key_name} = ?
            AND locked_at IS NULL
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute([
            \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
            $job_id,
        ]);
        return $statement->rowCount() === 1;
    }
}
