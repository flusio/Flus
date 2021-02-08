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
     * Find an available job and run it.
     *
     * @response 204 If no job to run
     * @response 500 If we took a job but we can't lock it
     * @response 200
     */
    public function run($request)
    {
        $job_dao = new models\dao\Job();
        $db_job = $job_dao->findNextJob();
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
            $job_dao->delete($db_job['id']);
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

        yield Response::text(200, '[Job worker started]');

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

        yield Response::text(200, '[Job worker stopped]');
    }

    /**
     * Handler to catch signals and stop the worker.
     */
    private function stopWatch()
    {
        $this->exit_watch = true;
    }
}
