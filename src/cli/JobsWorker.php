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

        $job = new $job_class();
        $job->perform(...$job_args);

        $job_dao->delete($db_job['id']);

        return Response::text(200, "job#{$db_job['id']}: done");
    }
}
