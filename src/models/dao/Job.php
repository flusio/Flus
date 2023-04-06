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
            'name',
            'created_at',
            'handler',
            'perform_at',
            'frequency',
            'queue',
            'locked_at',
            'number_attempts',
            'last_error',
            'failed_at',
        ]);
    }

    /**
     * Return the next available job (i.e. to perform and not locked during the
     * last hour) in the given queue. If queue is equal to 'all', it looks for
     * all queues.
     *
     * @param string queue
     *
     * @return array
     */
    public function findNextJob($queue)
    {
        $now = \Minz\Time::now();
        $lock_timeout = \Minz\Time::ago(1, 'hour');
        $values = [
            ':perform_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            ':lock_timeout' => $lock_timeout->format(\Minz\Model::DATETIME_FORMAT),
        ];

        $queue_placeholder = '';
        if ($queue !== 'all') {
            $queue_placeholder = 'AND queue = :queue';
            $values[':queue'] = $queue;
        }

        $sql = <<<SQL
            SELECT * FROM jobs
            WHERE (locked_at IS NULL OR locked_at <= :lock_timeout)
            AND perform_at <= :perform_at
            AND (number_attempts <= 25 OR frequency != '')
            {$queue_placeholder}
            ORDER BY perform_at ASC
        SQL;

        $statement = $this->prepare($sql);
        $statement->execute($values);
        $result = $statement->fetch();
        if ($result !== false) {
            return $result;
        } else {
            return null;
        }
    }

    /**
     * Lock the given job.
     *
     * The lock fails if a lock was already set in the last hour. This method
     * also increments the number_attempts value.
     *
     * @return boolean True if the lock suceeded, else false
     */
    public function lock($job_id)
    {
        $sql = <<<SQL
            UPDATE {$this->table_name}
            SET locked_at = :locked_at, number_attempts = number_attempts + 1
            WHERE {$this->primary_key_name} = :id
            AND (locked_at IS NULL OR locked_at <= :lock_timeout)
        SQL;

        $now = \Minz\Time::now();
        $lock_timeout = \Minz\Time::ago(1, 'hour');
        $statement = $this->prepare($sql);
        $statement->execute([
            ':locked_at' => $now->format(\Minz\Model::DATETIME_FORMAT),
            ':lock_timeout' => $lock_timeout->format(\Minz\Model::DATETIME_FORMAT),
            ':id' => $job_id,
        ]);
        return $statement->rowCount() === 1;
    }

    /**
     * Reschedule perform_at for a job with frequency.
     *
     * @param string $job_id
     */
    public function reschedule($job_id)
    {
        $db_job = $this->find($job_id);
        if (!$db_job['frequency']) {
            return;
        }

        $perform_at = $this->rescheduledPerformAt($db_job['perform_at'], $db_job['frequency']);
        $this->update($db_job['id'], [
            'locked_at' => null,
            'last_error' => null,
            'failed_at' => null,
            'perform_at' => $perform_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }

    /**
     * Mark a job as failed
     *
     * @param string $job_id
     * @param string $error
     */
    public function fail($job_id, $error)
    {
        $db_job = $this->find($job_id);
        if ($db_job['frequency']) {
            $new_perform_at = $this->rescheduledPerformAt($db_job['perform_at'], $db_job['frequency']);
        } else {
            $number_seconds = 5 + pow($db_job['number_attempts'], 4);
            $new_perform_at = \Minz\Time::fromNow($number_seconds, 'seconds');
        }

        $this->update($db_job['id'], [
            'locked_at' => null,
            'perform_at' => $new_perform_at->format(\Minz\Model::DATETIME_FORMAT),
            'last_error' => $error,
            'failed_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
        ]);
    }

    /**
     * Return a new perform_at, based on a frequency. It always return a date
     * in the future.
     *
     * @param string $current_perform_at
     * @param string $frequency
     *
     * @return \DateTime
     */
    private function rescheduledPerformAt($current_perform_at, $frequency)
    {
        $date = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $current_perform_at);
        while ($date <= \Minz\Time::now()) {
            $date->modify($frequency);
        }
        return $date;
    }
}
