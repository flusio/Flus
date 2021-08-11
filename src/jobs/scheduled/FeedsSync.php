<?php

namespace flusio\jobs\scheduled;

use flusio\jobs;
use flusio\models;
use flusio\services;

/**
 * Job to synchronize the feeds
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedsSync extends jobs\Job
{
    /**
     * Initialize the FeedsSync job.
     */
    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+15 seconds';
        $this->queue = 'fetchers';
    }

    /**
     * Install the correct number of jobs in database.
     */
    public function install()
    {
        $number_jobs_to_install = \Minz\Configuration::$application['feeds_sync_count'];

        $job_dao = new models\dao\Job();
        $jobs = $job_dao->listBy(['name' => $this->name]);

        $diff_count = $number_jobs_to_install - count($jobs);
        if ($diff_count > 0) {
            // If positive, we need to install more jobs
            for ($i = 0; $i < $diff_count; $i++) {
                $this->performLater();
            }
        } elseif ($diff_count < 0) {
            // If negative, we need to uninstall some jobs
            for ($i = 0; $i < abs($diff_count); $i++) {
                $job_dao->delete($jobs[$i]['id']);
            }
        }
    }

    /**
     * Execute the job.
     */
    public function perform()
    {
        $feed_fetcher_service = new services\FeedFetcher();

        $before = \Minz\Time::ago(1, 'hour');
        $collections = models\Collection::daoToList('listFeedsToFetch', $before, 25);
        foreach ($collections as $collection) {
            $has_lock = models\Collection::daoCall('lock', $collection->id);
            if (!$has_lock) {
                continue;
            }

            $feed_fetcher_service->fetch($collection);

            models\Collection::daoCall('unlock', $collection->id);
        }
    }
}
