<?php

namespace App\jobs\traits;

/**
 * JobInSerie provides utility methods to manage jobs that run on parallel.
 *
 * @phpstan-type Serie array{
 *     'number': int,
 *     'total': int,
 * }
 */
trait JobInSerie
{
    /**
     * Return the serie of the current job (number of the job and total of jobs
     * in the serie).
     *
     * It returns null if the serie cannot be found.
     *
     * @return ?Serie
     */
    public function currentSerie(): ?array
    {
        $jobs = self::listJobsInSerie();
        $jobs = array_values($jobs);

        $job_index = null;
        foreach ($jobs as $index => $job) {
            if ($job->id === $this->id) {
                $job_index = $index;
                break;
            }
        }

        if ($job_index === null) {
            return null;
        }

        return [
            'number' => $job_index,
            'total' => count($jobs),
        ];
    }

    /**
     * Return the jobs of the same serie (i.e. with the same name) ordered by id.
     *
     * @return \Minz\Job[]
     */
    public static function listJobsInSerie(): array
    {
        $sql = <<<'SQL'
            SELECT * FROM jobs
            WHERE name = :name
            ORDER BY id
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':name' => self::class,
        ]);
        return self::fromDatabaseRows($statement->fetchAll());
    }
}
