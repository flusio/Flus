<?php

namespace flusio\jobs;

use flusio\models;

/**
 * Base class for the others jobs. They must implement a `perform()` method.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Job
{
    /** @var string */
    public $name;

    /** @var \DateTime */
    public $perform_at;

    /** @var string */
    public $frequency;

    /**
     * Initialize the job.
     */
    public function __construct()
    {
        $this->name = get_called_class();
        $this->perform_at = \Minz\Time::now();
        $this->frequency = '';
    }

    /**
     * Store the job to be executed later by the JobsWorker.
     *
     * @param mixed $args,... Parameters to pass to the job
     *
     * @return integer The job id, or 0 if job adapter is set to test
     */
    public function performLater(...$args)
    {
        $job_adapter = \Minz\Configuration::$application['job_adapter'];
        if ($job_adapter === 'test') {
            $this->perform(...$args);
            return 0;
        }

        $handler = json_encode([
            'job_class' => get_called_class(),
            'job_args' => $args,
        ]);

        $job_dao = new models\dao\Job();
        return $job_dao->create([
            'name' => $this->name,
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
            'perform_at' => $this->perform_at->format(\Minz\Model::DATETIME_FORMAT),
            'frequency' => $this->frequency,
            'handler' => $handler,
        ]);
    }
}
