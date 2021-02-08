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
            AND number_attempts <= 25
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

    /**
     * Mark a job as failed
     *
     * @param string $db_job_id
     * @param string $error
     */
    public function fail($db_job_id, $error)
    {
        $db_job = $this->find($db_job_id);
        $number_seconds = 5 + pow($db_job['number_attempts'], 4);
        $new_perform_at = \Minz\Time::fromNow($number_seconds, 'seconds');
        $this->update($db_job['id'], [
            'locked_at' => null,
            'perform_at' => $new_perform_at->format(\Minz\Model::DATETIME_FORMAT),
            'last_error' => $error,
            'failed_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }

    /**
     * Delete all the jobs
     *
     * @throws \PDOException if an error occured in the SQL syntax
     */
    public function deleteAll()
    {
        return $this->exec("DELETE FROM {$this->table_name}");
    }
}
