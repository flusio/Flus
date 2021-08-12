<?php

namespace flusio\cli;

use Minz\Response;
use flusio\jobs;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class JobsWorker
{
    /**
     * List all the current jobs
     *
     * @response 200
     */
    public function index($request)
    {
        $job_dao = new models\dao\Job();
        $db_jobs = $job_dao->listAll();
        $result = [];
        foreach ($db_jobs as $db_job) {
            $job_as_text = "job#{$db_job['id']} at {$db_job['perform_at']}";
            $job_as_text .= " {$db_job['number_attempts']} attempts";
            if ($db_job['locked_at']) {
                $job_as_text .= ' (locked)';
            }
            if ($db_job['failed_at']) {
                $job_as_text .= ' (failed)';
            }

            $result[] = $job_as_text;
        }

        return Response::text(200, implode("\n", $result));
    }

    /**
     * Delete all the jobs.
     *
     * @response 200
     */
    public function clear($request)
    {
        $job_dao = new models\dao\Job();
        $count = $job_dao->deleteAll();
        return Response::text(200, "{$count} jobs deleted");
    }

    /**
     * Find an available job and run it.
     *
     * @request_param string $queue Selects job in the given queue (default: all)
     *
     * @response 204 If no job to run
     * @response 500 If we took a job but we can't lock it
     * @response 200
     */
    public function run($request)
    {
        $job_dao = new models\dao\Job();
        $queue = $request->param('queue', 'all');
        $db_job = $job_dao->findNextJob($queue);
        if (!$db_job) {
            return Response::noContent();
        }

        if (!$job_dao->lock($db_job['id'])) {
            return Response::internalServerError(); // @codeCoverageIgnore
        }

        $handler = json_decode($db_job['handler'], true);
        $job_class = $handler['job_class'];
        $job_args = $handler['job_args'];

        try {
            $job = new $job_class();
            $job->perform(...$job_args);

            if ($db_job['frequency']) {
                $job_dao->reschedule($db_job['id']);
            } else {
                $job_dao->delete($db_job['id']);
            }
        } catch (\Exception $exception) {
            $job_dao->fail($db_job['id'], (string)$exception);
            return Response::text(500, "job#{$db_job['id']}: failed");
        }

        return Response::text(200, "job#{$db_job['id']}: done");
    }

    /**
     * Start a job worker which call `run()` in a loop. This action should be
     * called via a systemd service, or as any other kind of "init" service.
     *
     * Responses are yield during the lifetime of the action.
     *
     * @request_param string $queue Selects job in the given queue (default: all)
     *
     * @response 204 If no job to run
     * @response 500 If we took a job but we can't lock it
     * @response 200
     */
    public function watch($request)
    {
        \pcntl_async_signals(true);
        \pcntl_signal(SIGTERM, [$this, 'stopWatch']);
        \pcntl_signal(SIGINT, [$this, 'stopWatch']);
        \pcntl_signal(SIGALRM, [$this, 'stopWatch']); // used for tests
        $this->exit_watch = false;

        $queue = $request->param('queue', 'all');
        yield Response::text(200, "[Job worker ({$queue}) started]");

        while (true) {
            yield $this->run($request);

            if (!$this->exit_watch) {
                sleep(5);
            }

            // exit_watch can be set to true during sleep(), so don't merge the
            // two conditions with a "else"!
            if ($this->exit_watch) {
                break;
            }
        }

        yield Response::text(200, "[Job worker ({$queue}) stopped]");
    }

    /**
     * Handler to catch signals and stop the worker.
     */
    public function stopWatch()
    {
        $this->exit_watch = true;
    }
}
