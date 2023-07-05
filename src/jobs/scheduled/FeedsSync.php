<?php

namespace flusio\jobs\scheduled;

use flusio\models;
use flusio\services;

/**
 * Job to synchronize the feeds
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedsSync extends \Minz\Job
{
    /**
     * Install the correct number of jobs in database.
     */
    public static function install(): void
    {
        $number_jobs_to_install = \Minz\Configuration::$application['job_feeds_sync_count'];

        $jobs = \Minz\Job::listBy([
            'name' => self::class,
        ]);

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

        // There are two strategies to sync feeds. The first one is to select
        // randomly 25 active feeds (i.e. followed by at least one active user).
        // The second strategy is to select the 25 feeds that haven’t been
        // fetched for the longest time. This second strategy is triggered only
        // 1 out of 6. This allows multiple jobs to run in parallel on (mostly)
        // different feeds (first strategy), while being sure to sync all the
        // feeds (second strategy).
        $before = \Minz\Time::ago(1, 'hour');
        $strategy_choice = random_int(1, 6);
        if ($strategy_choice < 6) {
            $collections = models\Collection::listActiveFeedsToFetch($before, 25);
        } else {
            $collections = models\Collection::listOldestFeedsToFetch($before, 25);
        }

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
