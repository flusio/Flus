<?php

namespace App\jobs\scheduled;

use App\models;
use App\services;

/**
 * Job to synchronize the feeds
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedsSync extends \Minz\Job
{
    use \App\jobs\traits\JobInSerie;

    /**
     * Install the correct number of jobs in database.
     */
    public static function install(): void
    {
        $number_jobs_to_install = \App\Configuration::$application['job_feeds_sync_count'];

        $jobs = self::listJobsInSerie();

        $diff_count = $number_jobs_to_install - count($jobs);
        if ($diff_count > 0) {
            // If positive, we need to install more jobs
            for ($i = 0; $i < $diff_count; $i++) {
                $feeds_sync_job = new self();
                $feeds_sync_job->performAsap();
            }
        } elseif ($diff_count < 0) {
            // If negative, we need to uninstall some jobs
            for ($i = 0; $i < abs($diff_count); $i++) {
                \Minz\Job::delete($jobs[$i]->id);
            }
        }
    }

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
     * Execute the job.
     */
    public function perform(): void
    {
        $feed_fetcher_service = new services\FeedFetcher();

        $serie = $this->currentSerie();
        $collections = models\Collection::listFeedsToFetch(max: 25, serie: $serie);

        foreach ($collections as $collection) {
            $has_lock = $collection->lock();
            if (!$has_lock) {
                continue;
            }

            try {
                $feed_fetcher_service->fetch($collection);
            } catch (\Exception $e) {
                \Minz\Log::error("Error while syncing feed {$collection->id}: {$e->getMessage()}");
            }

            $collection->unlock();
        }
    }
}
